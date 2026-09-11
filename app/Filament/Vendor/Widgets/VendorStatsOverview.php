<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\Booking;
use App\Models\Car;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\VendorSubscriptionService;

class VendorStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (!$vendor) {
            return [];
        }

        $service = app(VendorSubscriptionService::class);
        $sub     = $service->getCurrent($vendor);

        // Info paket dari subscription aktif, fallback ke plan lama
        if ($sub && $sub->package) {
            $packageName    = $sub->package->name;
            $commissionRate = $sub->package->commission_rate;
            $maxCars        = $sub->snapshot_features['max_cars'] ?? $sub->package->maxCars();
            $packageColor   = match($sub->status) {
                'active'       => 'success',
                'grace_period' => 'warning',
                default        => 'danger',
            };
            $packageDesc = match($sub->status) {
                'grace_period'   => 'Grace period — segera perpanjang',
                'expired_locked' => '⚠️ Paket expired — mobil disembunyikan',
                default          => 'Komisi: ' . $commissionRate . '%',
            };
        } else {
            $packageName  = $vendor->plan->label();
            $maxCars      = $vendor->plan->maxCars();
            $packageColor = 'gray';
            $packageDesc  = 'Tidak ada paket aktif';
        }

        $totalCars     = Car::where('vendor_id', $vendor->id)->count();
        $publishedCars = Car::where('vendor_id', $vendor->id)->where('status', 'published')->count();
        $suspendedBySubscription = Car::where('vendor_id', $vendor->id)
            ->where('status', 'suspended')
            ->whereIn('unavailability_reason', ['subscription_expired', 'plan_downgrade'])
            ->count();

        if ($maxCars === -1 || $maxCars === null) {
            $carDescription = "{$publishedCars} aktif · {$totalCars} total (unlimited)";
        } else {
            $carDescription = "{$publishedCars}/{$maxCars} aktif";
            if ($suspendedBySubscription > 0) {
                $carDescription .= " · {$suspendedBySubscription} disembunyikan";
            }
        }

        $carColor = 'success';
        if ($suspendedBySubscription > 0) {
            $carColor = 'danger';
        } elseif ($maxCars && $maxCars !== -1 && $publishedCars >= $maxCars) {
            $carColor = 'warning';
        }

        return [
            Stat::make('Paket Aktif', $packageName)
                ->description($packageDesc)
                ->descriptionIcon('heroicon-o-sparkles')
                ->color($packageColor),

            Stat::make('Total Armada', $publishedCars)
                ->description($carDescription)
                ->descriptionIcon('heroicon-o-truck')
                ->color($carColor),

            Stat::make('Pemesanan Baru', Booking::where('vendor_id', $vendor->id)
                ->where('status', 'awaiting_vendor')->count())
                ->description('Menunggu konfirmasi')
                ->descriptionIcon('heroicon-o-bell')
                ->color('warning'),

            Stat::make('Pendapatan Bulan Ini', 'Rp ' . number_format(
                \App\Models\Payout::where('vendor_id', $vendor->id)
                    ->where('status', 'paid')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount'), 0, ',', '.'
            ))
                ->description('Dari payout yang sudah dibayar bulan ini')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Total Semua Pendapatan', 'Rp ' . number_format(
                \App\Models\Payout::where('vendor_id', $vendor->id)
                    ->where('status', 'paid')
                    ->sum('amount'), 0, ',', '.'
            ))
                ->description('Akumulasi seluruh payout yang diterima')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
