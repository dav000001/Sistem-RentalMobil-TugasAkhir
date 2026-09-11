<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Livewire;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Override Filament DatabaseNotifications agar tampilkan semua notifikasi (read + unread)
        Livewire::component(
            'filament-notifications::database-notifications',
            \App\Livewire\DatabaseNotifications::class
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Rental Mobil')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Blue,
            ])            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                \App\Filament\Admin\Widgets\StatsOverview::class,
                \App\Filament\Admin\Widgets\VendorPayoutBreakdown::class,
            ])
            ->navigationItems([
                NavigationItem::make('Komplain Customer')
                    ->url('/admin/complaints')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->sort(10),
                NavigationItem::make('Laporan Vendor')
                    ->url('/admin/vendor-reports')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->sort(11),
                NavigationItem::make('Laporan Keuangan')
                    ->url('/admin/reports')
                    ->icon('heroicon-o-chart-bar')
                    ->sort(12),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('120s') // naikkan interval polling agar tidak sering trigger session check
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // AuthenticateSession dihapus — sering menyebabkan logout paksa karena
                // konflik session hash antar guard (web/vendor/admin pakai tabel session sama)
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\PanelSessionTimeout::class . ':admin',
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureUserIsAdmin::class,
            ])
            ->authGuard('admin')
            ->requiresEmailVerification(false); // Admin panel tidak perlu verifikasi email
    }
}
