<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Info extends Model
{
    use HasFactory;

    public $incrementing =  false;
    protected $table = 'infos';
    protected $primaryKey = 'uuid';
    protected $casts = [
        'id'               => 'string',
        'about_us'         => 'array',
        'vision'           => 'array',
        'mission'          => 'array',
        'offices_location' => 'array',
    ];
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'no_whatsapp',
        'address',
        'email',
        'facebook',
        'instagram',
        'youtube',
        'logo',
        'meta_domain',
        'meta_title',
        'meta_desc',
        'meta_keywords',
        'meta_image',
        'proposal',
        'partner_guide',
        'privacy_policy',
        'terms_conditions',
        'about_us',
        'vision',
        'mission',
        'offices_location',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getInfo()
    {
        return $this->first();
    }

    public function aboutPage(): array
    {
        $about = $this->about_us ?? [];
        $empty = ['id' => '', 'en' => ''];

        return [
            'hero'     => $about['hero'] ?? $empty,
            'who'      => [
                'id' => $this->splitParagraphs($about['who']['id'] ?? ''),
                'en' => $this->splitParagraphs($about['who']['en'] ?? ''),
            ],
            'services' => $about['services'] ?? [],
            'values'   => $about['values'] ?? [],
            'vision'   => $this->vision ?? $empty,
            'missions' => $this->mission ?? [],
            'image'    => $this->about_image
                ? asset('storage/' . $this->about_image)
                : asset('assets/front-end/img/profile.webp'),
            'cta'      => [
                'title' => $about['cta']['title'] ?? [
                    'id' => 'Perlu pengujian atau pemetaan risiko kepatuhan?',
                    'en' => 'Need testing or a compliance risk assessment?',
                ],
                'text'  => $about['cta']['text'] ?? [
                    'id' => 'Ceritakan kebutuhan Anda lewat tiket konsultasi. Tim kami akan menghubungi Anda untuk membahas langkah berikutnya.',
                    'en' => 'Tell us what you need through a consultation ticket. Our team will contact you to discuss the next steps.',
                ],
            ],
        ];
    }

    private function splitParagraphs(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', $text))));
    }
}
