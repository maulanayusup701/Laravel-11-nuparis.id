<?php

namespace App\Providers\Filament;

use \App\Http\Middleware\BlockMemberAccessToPanel;
use App\Filament\Pages\Auth\LoginCustom;
use App\Http\Responses\CustomLoginResponse;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Support\Facades\URL;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ManagementPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        URL::forceScheme('https');
        return $panel
            ->default()
            ->id('management')
            ->path('management')
            ->login(LoginCustom::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            // cluster
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')

            ->navigationGroups([
                'TOSS',
                'Resources',
                'Activities',
                'Articles',
                'Events',
                'Letters',
                'Hero',
                'News',
                'Laporan',
                'Partner',
                'Property',
                'Review',
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                BlockMemberAccessToPanel::class,
            ])
            // Cegah halaman bergeser ke bawah saat pindah langkah di Wizard
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): HtmlString => new HtmlString(<<<'HTML'
                <script>
                    document.addEventListener('click', (e) => {
                        const tombol = e.target.closest('button');
                        if (!tombol) return;

                        const teks = tombol.textContent.trim().toLowerCase();
                        if (!['selanjutnya', 'sebelumnya', 'next', 'previous'].includes(teks)) return;

                        const posisi = window.scrollY;
                        const mulai = performance.now();
                        let aktif = true;

                        const lepas = () => { aktif = false; };
                        ['wheel', 'touchmove', 'keydown', 'mousedown'].forEach((ev) =>
                            window.addEventListener(ev, lepas, { once: true, passive: true })
                        );

                        const kunci = () => {
                            if (!aktif || performance.now() - mulai > 1000) return;
                            if (Math.abs(window.scrollY - posisi) > 2) {
                                window.scrollTo({ top: posisi, behavior: 'instant' });
                            }
                            requestAnimationFrame(kunci);
                        };
                        requestAnimationFrame(kunci);
                    }, true);
                </script>
            HTML)
            )
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
                // tidak perlu 'role:admin' di sini jika kamu sudah pakai middleware sendiri
            ])->sidebarCollapsibleOnDesktop();
    }

    public function boot(): void
    {
        app()->bind(LoginResponse::class, CustomLoginResponse::class);
    }
}
