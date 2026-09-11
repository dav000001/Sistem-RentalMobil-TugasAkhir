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

class VendorPanelProvider extends PanelProvider
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
            ->id('vendor')
            ->path('vendor')
            ->login(\App\Filament\Vendor\Pages\Login::class)
            ->brandName('Rental Mobil')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\\Filament\\Vendor\\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\\Filament\\Vendor\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\\Filament\\Vendor\\Widgets')
            ->widgets([
                \App\Filament\Vendor\Widgets\VendorStatsOverview::class,
            ])
            ->navigationItems([
                NavigationItem::make('Billing & Paket')
                    ->url('/vendor/billing')
                    ->icon('heroicon-o-credit-card')
                    ->sort(99),
                NavigationItem::make('Komplain Customer')
                    ->url('/vendor/complaints')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->badge(fn () => (string) (\App\Models\Complaint::where('vendor_id', auth('vendor')->user()?->vendor?->id)
                        ->where('status', 'forwarded_to_vendor')
                        ->count() ?: null))
                    ->sort(10),
                NavigationItem::make('Laporan Saya ke Admin')
                    ->url('/vendor/my-reports')
                    ->icon('heroicon-o-megaphone')
                    ->badge(fn () => (string) (\App\Models\Complaint::where('reporter_id', auth('vendor')->id())
                        ->whereNotIn('status', ['resolved', 'rejected'])
                        ->count() ?: null))
                    ->sort(11),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('120s') // naikkan interval agar tidak sering trigger session check
            ->renderHook(
                'panels::auth.login.form.after',
                fn () => '<div class="mt-4 text-center border-t border-gray-200 pt-4">
                    <a href="' . url('/password-help') . '" class="text-sm text-orange-600 hover:underline font-medium">
                        🔐 Lupa password? Minta bantuan admin
                    </a>
                </div>'
            )
            ->renderHook(
                'panels::body.start',
                function () {
                    $user = auth('vendor')->user();
                    if (!$user) return '';

                    // Banner selamat datang jika ada notifikasi approval yang belum dibaca
                    $approvalNotif = $user->unreadNotifications()
                        ->where('data->type', 'vendor_approved')
                        ->latest()
                        ->first();

                    $bannerHtml = '';
                    if ($approvalNotif) {
                        $approvalNotif->markAsRead();
                        $bannerHtml = '<div style="background: linear-gradient(135deg, #059669, #047857); color: white; padding: 14px 20px; text-align: center; font-size: 14px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <span style="font-size: 20px;">🎉</span>
                            <span>Selamat! Akun vendor Anda telah <strong>disetujui dan aktif</strong>. Mulai tambahkan mobil pertama Anda!</span>
                            <a href="/vendor/cars/create" style="background: white; color: #047857; padding: 4px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; text-decoration: none; margin-left: 8px; white-space: nowrap;">+ Tambah Mobil →</a>
                        </div>';
                    }

                    $vendor = $user->vendor;
                    if (!$vendor) return $bannerHtml;

                    // ── Banner subscription via sistem baru ──────────────────────
                    $service = app(\App\Services\VendorSubscriptionService::class);
                    $sub     = $service->getCurrent($vendor);

                    $subscriptionBanner = '';

                    if ($sub) {
                        if ($sub->status === 'expired_locked') {
                            $subscriptionBanner = '<div style="background:#dc2626;color:white;padding:12px 20px;text-align:center;font-size:14px;font-weight:500;">
                                ⚠️ Paket <strong>' . e($sub->package->name) . '</strong> Anda telah <strong>berakhir</strong>. Semua mobil disembunyikan dari pencarian.
                                <a href="/vendor/billing" style="background:white;color:#dc2626;padding:3px 12px;border-radius:20px;font-weight:700;font-size:13px;text-decoration:none;margin-left:8px;">Perpanjang Sekarang →</a>
                            </div>';
                        } elseif ($sub->status === 'grace_period') {
                            $graceUntil = $sub->grace_until?->format('d M Y') ?? '-';
                            $subscriptionBanner = '<div style="background:#d97706;color:white;padding:12px 20px;text-align:center;font-size:14px;font-weight:500;">
                                ⏰ Paket <strong>' . e($sub->package->name) . '</strong> dalam grace period. Mobil masih tampil hingga <strong>' . $graceUntil . '</strong>.
                                <a href="/vendor/billing" style="background:white;color:#d97706;padding:3px 12px;border-radius:20px;font-weight:700;font-size:13px;text-decoration:none;margin-left:8px;">Perpanjang →</a>
                            </div>';
                        } elseif ($sub->status === 'active' && $sub->expires_at) {
                            $daysLeft = (int) now()->diffInDays($sub->expires_at, false);
                            if ($daysLeft <= 7 && $daysLeft >= 0) {
                                $color = $daysLeft <= 3 ? '#dc2626' : '#d97706';
                                $expiresAt = $sub->expires_at->format('d M Y');
                                $subscriptionBanner = '<div style="background:' . $color . ';color:white;padding:12px 20px;text-align:center;font-size:14px;font-weight:500;">
                                    ⏰ Paket <strong>' . e($sub->package->name) . '</strong> berakhir <strong>' . $expiresAt . '</strong> (' . $daysLeft . ' hari lagi).
                                    <a href="/vendor/billing" style="background:white;color:' . $color . ';padding:3px 12px;border-radius:20px;font-weight:700;font-size:13px;text-decoration:none;margin-left:8px;">Perpanjang →</a>
                                </div>';
                            }
                        }
                    } elseif (!$sub) {
                        // Tidak ada subscription sama sekali
                        $subscriptionBanner = '<div style="background:#dc2626;color:white;padding:12px 20px;text-align:center;font-size:14px;font-weight:500;">
                            ⚠️ Anda belum memiliki paket aktif. Mobil tidak akan tampil di pencarian.
                            <a href="/vendor/billing" style="background:white;color:#dc2626;padding:3px 12px;border-radius:20px;font-weight:700;font-size:13px;text-decoration:none;margin-left:8px;">Pilih Paket →</a>
                        </div>';
                    }

                    // ── Banner mobil disembunyikan karena downgrade/expired ───────
                    $hiddenCarsBanner = '';
                    $hiddenCarsCount = \App\Models\Car::where('vendor_id', $vendor->id)
                        ->where('status', 'suspended')
                        ->whereIn('unavailability_reason', ['subscription_expired', 'plan_downgrade'])
                        ->count();

                    if ($hiddenCarsCount > 0 && $sub && $sub->status === 'active') {
                        // Subscription sudah aktif tapi masih ada mobil tersembunyi
                        // (edge case: mobil plan_downgrade tidak otomatis re-publish)
                        $reason = \App\Models\Car::where('vendor_id', $vendor->id)
                            ->where('status', 'suspended')
                            ->whereIn('unavailability_reason', ['subscription_expired', 'plan_downgrade'])
                            ->value('unavailability_reason');

                        $reasonLabel = $reason === 'plan_downgrade'
                            ? 'melebihi batas paket aktif (' . ($sub->snapshot_features['max_cars'] ?? '?') . ' mobil)'
                            : 'paket sebelumnya berakhir';

                        $hiddenCarsBanner = '<div style="background:#7c3aed;color:white;padding:12px 20px;text-align:center;font-size:14px;font-weight:500;">
                            🚗 <strong>' . $hiddenCarsCount . ' mobil</strong> disembunyikan karena ' . $reasonLabel . '.
                            <a href="/vendor/cars" style="background:white;color:#7c3aed;padding:3px 12px;border-radius:20px;font-weight:700;font-size:13px;text-decoration:none;margin-left:8px;">Kelola Mobil →</a>
                        </div>';
                    }

                    return $bannerHtml . $subscriptionBanner . $hiddenCarsBanner;
                }
            )
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
                \App\Http\Middleware\PanelSessionTimeout::class . ':vendor',
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureVendorApprovedForPanel::class,
            ])
            ->authGuard('vendor')
            ->requiresEmailVerification(false); // Vendor panel tidak perlu verifikasi email
    }
}
