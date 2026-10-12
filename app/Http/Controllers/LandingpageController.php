<?php

namespace App\Http\Controllers;

use \App\Models\Activity;
use \App\Models\Article;
use \App\Models\News;
use \App\Models\Partner;
use \App\Models\Review;
use App\Models\CertificateItem;
use App\Models\DocumentToss;
use App\Models\Event;
use App\Models\Hero;
use App\Models\Info;
use App\Models\Order;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;

class LandingpageController extends Controller
{
    public function index()
    {
        $events = Event::query()->withCount('participants')->latest()->get();
        $paidStatuses = ['paid', 'success'];

        $orderCounts = Order::query()
            ->selectRaw('order_reference_uuid, COUNT(*) as total')
            ->where('order_reference_type', 'event')
            ->whereIn('order_transaction_status', $paidStatuses)
            ->groupBy('order_reference_uuid')
            ->pluck('total', 'order_reference_uuid');

        $events = $events->map(function ($event) use ($orderCounts) {
            $orderRegistered = (int) ($orderCounts[$event->uuid] ?? 0);
            $participantRegistered = (int) ($event->participants_count ?? 0);
            $registeredCount = max($orderRegistered, $participantRegistered);

            $event->registeredCount = $registeredCount;
            $event->event_registered = $registeredCount;

            return $event;
        });

        $offices = collect(config('app.offices_location.list'))
            ->filter(fn($office) => !empty($office['office_name']))
            ->values()
            ->all();
        $regionOrder = config('app.offices_location.region_order');
        $groupedOffices = collect($offices)
            ->groupBy('office_region')
            ->sortBy(function ($group, $region) use ($regionOrder) {
                $pos = array_search($region, $regionOrder);
                return $pos === false ? 999 : $pos;
            });
        $firstOffice = $offices[0] ?? null;

        $office_location = new Fluent([
            'offices' => $offices,
            'grouped' => $groupedOffices,
            'first_embed' => $firstOffice['office_embed_src'] ?? null,
            'first_external' => $firstOffice['office_external_url'] ?? null,
            'total' => count($offices),
        ]);

        $infos = (new Info())->getInfo();

        // ==== SEO ====
        $seoImage = !empty($infos->meta_image) ? Storage::disk('public')->url($infos->meta_image) : null;

        $jsonld = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => url('/'),
                'logo' => $seoImage,
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
        ];

        $feedUrls = [
            ['url' => route('feeds.article'), 'title' => 'Artikel Terbaru'],
            ['url' => route('feeds.activity'), 'title' => 'Portofolio Kegiatan'],
            ['url' => route('feeds.event'), 'title' => 'Event Terbaru'],
            ['url' => route('feeds.property'), 'title' => 'Listing Properti'],
        ];

        return view('front-end.landingpage', [
            'title' => $infos->meta_title,
            'events' => $events,
            'activities' => (new Activity())->getActivity(),
            'partnerLayers' => (new Partner())->getPartner(),
            'articles' => (new Article())->getArticle(),
            'news' => (new News())->getNews(),
            'infos' => $infos,
            'offices' => $office_location,
            'reviews' => (new Review())->getReview(),
            'members' => (new User())->getMembers(),
            'consultants' => (new User())->getconsultants(),
            'heroes' => Cache::remember('landing.heroes', now()->addHours(6), fn() => (new Hero())->getAssets()),
            'properties' => (new Property())->getProperties(),
            'agencies_footer' => (new Partner())->getAgencies(),

            // ==== SEO ====
            'seo_title' => $infos->meta_title,
            'seo_description' => $infos->meta_description,
            'seo_image' => $seoImage,
            'seo_type' => 'website',
            'canonical_url' => url('/'),
            'jsonld' => $jsonld,
            'feed_urls' => $feedUrls,
        ]);
    }

    public function news()
    {
        return view('front-end.news-more', [
            'title' => 'News | ' . config('app.name'),
            'infos' => (new Info())->getInfo(),
            'agencies_footer' => (new Partner())->getAgencies(),
        ]);
    }

    public function newsData()
    {
        try {
            $news = News::all();
            return response()->json([
                'success' => true,
                'newsData' => $news->toArray(),
                'total' => $news->count()
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('news API error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage(),
                'eventData' => []
            ], 500);
        }
    }

    public function aboutUs()
    {
        $info = (new Info)->getInfo() ?? new Info;

        return view('front-end.about-us', [
            'title'           => 'Tentang Kami | ' . config('app.name'),
            'infos'           => $info,
            'about'           => $info->aboutPage(),
            'agencies_footer' => (new Partner)->getAgencies(),
        ]);
    }

    public function toss($slug)
    {
        // Ambil data dokumen berdasarkan slug
        $toss = DocumentToss::where('document_slug', $slug)->first();

        // Jika data tidak ditemukan, tampilkan halaman 404
        if (!$toss) {
            abort(404);
        }

        $start = $toss->document_start;
        $end = $toss->document_end;

        // Nilai default jika kedua tanggal null
        $expired = '-';
        $statusDoc = 'Valid';

        // CEK KONDISI TANGGAL:
        // 1. Kondisi: kedua tanggal terisi
        if ($start && $end) {
            $startCarbon = Carbon::parse($start);
            $endCarbon = Carbon::parse($end);

            // Format tanggal berdasarkan kesamaan bulan & tahun
            $expired = $startCarbon->month === $endCarbon->month && $startCarbon->year === $endCarbon->year
                ? $startCarbon->format('j') . ' - ' . $endCarbon->format('j F Y')  // "25 - 31 Juli 2025"
                : $startCarbon->format('j M Y') . ' - ' . $endCarbon->format('j M Y'); // "25 Jul 2025 - 31 Agu 2025"

            // Tentukan status: Berlaku / Tidak Berlaku
            $statusDoc = now()->gt($endCarbon) ? 'Tidak Berlaku' : 'Berlaku';
        }
        // 2. Kondisi: start terisi, end null
        elseif ($start && !$end) {
            $expired = 'Mulai ' . Carbon::parse($start)->format('j F Y'); // "Mulai 25 Juli 2025"
            // Status tetap Valid (default)
        }
        // 3. Kondisi: start null, end terisi
        elseif (!$start && $end) {
            $endCarbon = Carbon::parse($end);
            $expired = 'Berakhir ' . $endCarbon->format('j F Y'); // "Berakhir 31 Agustus 2025"
            // Tentukan status berdasarkan tanggal akhir
            $statusDoc = now()->gt($endCarbon) ? 'Tidak Berlaku' : 'Berlaku';
        }
        // 4. Kondisi: kedua null - menggunakan nilai default

        // Ambil file dokumen
        $urlDocument = $toss->document_final_path;

        // Kirim data ke view
        return view('front-end.toss', [
            'title' => 'Toss | ' . config('app.name'),
            'documentData' => collect([
                'id' => $toss->document_no,
                'name' => $toss->document_name,
                'description' => $toss->document_description,
                'notes' => $toss->document_notes,
                'expired' => $expired,
                'status' => $statusDoc,
                'signedBy' => $toss->document_bySign,
                'receivedBy' => $toss->document_toReceive,
                'action' => $toss->document_action,
            ]),
            'url_document' => $urlDocument,
            'size_document' => Storage::size($urlDocument),
            'size_mime_type' => Storage::mimeType($urlDocument),
            'qr_code' => $toss->qr_path,
            'statusDoc' => $statusDoc,
            'infos' => ((new Info)->getInfo()),
            'agencies_footer' => (new Partner())->getAgencies(),
        ]);
    }

    public function certificate($slug)
    {
        $item = CertificateItem::where('slug', $slug)->first();

        if (! $item) {
            abort(404);
        }

        $start = $item->valid_from;
        $end = $item->valid_until;

        $expired = '-';
        $statusDoc = 'Valid';

        if ($start && $end) {
            $startCarbon = Carbon::parse($start);
            $endCarbon = Carbon::parse($end);

            $expired = $startCarbon->month === $endCarbon->month && $startCarbon->year === $endCarbon->year
                ? $startCarbon->format('j') . ' - ' . $endCarbon->format('j F Y')
                : $startCarbon->format('j M Y') . ' - ' . $endCarbon->format('j M Y');

            $statusDoc = now()->gt($endCarbon) ? 'Tidak Berlaku' : 'Berlaku';
        } elseif ($start && ! $end) {
            $expired = 'Mulai ' . Carbon::parse($start)->format('j F Y');
        } elseif (! $start && $end) {
            $endCarbon = Carbon::parse($end);
            $expired = 'Berakhir ' . $endCarbon->format('j F Y');
            $statusDoc = now()->gt($endCarbon) ? 'Tidak Berlaku' : 'Berlaku';
        }

        return view('front-end.certificate', [
            'title' => 'Sertifikat | ' . config('app.name'),
            'certificateData' => collect([
                'id' => $item->slug,
                'deskripsi' => $item->deskripsi,
                'catatan' => $item->catatan,
                'expired' => $expired,
                'dynamic_fields' => $item->fields,
                'nama' => $item->nama,
                'keterangan' => $item->keterangan,
                'tempat' => $item->tempat,
                'tanggal' => $item->tanggal,
                'tahun' => $item->tahun,
            ]),
            'url_document' => $item->file_path,
            'size_document' => $item->file_path ? Storage::disk('public')->size($item->file_path) : null,
            'size_mime_type' => $item->file_path ? Storage::disk('public')->mimeType($item->file_path) : null,
            'qr_code' => $item->qr_path,
            'statusDoc' => $statusDoc,
            'infos' => ((new Info)->getInfo()),
            'agencies_footer' => (new Partner())->getAgencies(),
        ]);
    }
}
