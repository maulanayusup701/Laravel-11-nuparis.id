<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Article;
use App\Models\Event;
use App\Models\Property;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = Cache::remember('sitemap.urls', now()->addHour(), function (): array {
            $urls = [];

            // Halaman statis
            foreach (
                [
                    'landingpage',
                    'about-us',
                    'article-more',
                    'activity-more',
                    'event-more',
                    'property-more',
                    'halal.index',
                ] as $name
            ) {
                $urls[] = ['url' => route($name), 'lastmod' => null];
            }

            // Halaman detail: [model, nama route, kolom slug]
            foreach (
                [
                    [Article::class, 'article-detail', 'article_slug'],
                    [Activity::class, 'activity-detail', 'activity_slug'],
                    [Event::class, 'event-detail', 'event_slug'],
                    [Property::class, 'property-detail', 'property_slug'],
                ] as [$model, $routeName, $slugColumn]
            ) {
                $model::query()
                    ->select([$slugColumn, 'updated_at'])
                    ->cursor()
                    ->each(function ($item) use (&$urls, $routeName, $slugColumn): void {
                        $urls[] = [
                            'url' => route($routeName, $item->{$slugColumn}),
                            'lastmod' => $item->updated_at?->toAtomString(),
                        ];
                    });
            }

            return $urls;
        });

        return response()
            ->view('front-end.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
