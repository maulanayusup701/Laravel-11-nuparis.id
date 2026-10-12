<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',

            // Panel admin & area login
            'Disallow: /management',
            'Disallow: /dashboard',
            'Disallow: /edit-profile',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /filament',
            'Disallow: /filament-excel',

            // Laporan & utilitas internal
            'Disallow: /report/download',
            'Disallow: /clear',
            'Disallow: /down',
            'Disallow: /up$',
            'Disallow: /linkstorage',

            // Debug & framework
            'Disallow: /_debugbar',
            'Disallow: /livewire',
            'Disallow: /sanctum',

            // API & endpoint transaksi
            'Disallow: /api/',
            'Disallow: /event/midtrans',
            'Disallow: /event/register',

            // Endpoint data (AJAX/load more)
            'Disallow: /*/data',

            // Tiket (detail & pencarian bersifat privat)
            'Disallow: /ticket/',

            // Halal: index & create terbuka, hanya halaman edit yang diblokir
            'Disallow: /halal/*/edit',

            // Dokumen TOSS
            'Disallow: /toss',

            'Sitemap: ' . route('sitemap'),
        ];

        return response(implode("\n", $lines) . "\n")
            ->header('Content-Type', 'text/plain');
    }
}
