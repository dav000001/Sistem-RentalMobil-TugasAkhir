<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Base query: booking yang payment-nya sudah paid dalam rentang tanggal.
     * EXCLUDE booking yang sudah di-refund (payment.status = 'refunded').
     * Konsisten dengan dashboard admin.
     */
    private function baseQuery(Carbon $from, Carbon $to)
    {
        return Booking::whereHas('payment', function ($q) use ($from, $to) {
            $q->where('status', 'paid')
              ->whereBetween('paid_at', [$from, $to]);
        });
    }

    /**
     * Base query yang exclude refunded payments untuk akurasi laporan keuangan.
     */
    private function baseQueryExcludeRefunded(Carbon $from, Carbon $to)
    {
        return Booking::whereHas('payment', function ($q) use ($from, $to) {
            $q->whereIn('status', ['paid']) // hanya yang benar-benar paid, bukan refunded
              ->whereBetween('paid_at', [$from, $to]);
        });
    }

    public function gmvBetween(Carbon $from, Carbon $to): int
    {
        return (int) $this->baseQueryExcludeRefunded($from, $to)->sum('total');
    }

    public function commissionBetween(Carbon $from, Carbon $to): int
    {
        return (int) $this->baseQueryExcludeRefunded($from, $to)->sum('platform_fee');
    }

    public function bookingCountBetween(Carbon $from, Carbon $to): int
    {
        return $this->baseQueryExcludeRefunded($from, $to)->count();
    }

    public function activeVendorsBetween(Carbon $from, Carbon $to): int
    {
        return $this->baseQueryExcludeRefunded($from, $to)
            ->distinct('vendor_id')
            ->count('vendor_id');
    }

    public function dailyBreakdown(Carbon $from, Carbon $to): array
    {
        $cacheKey = "report:daily:{$from->toDateString()}:{$to->toDateString()}";
        return Cache::remember($cacheKey, 600, function () use ($from, $to) {
            return Booking::whereHas('payment', function ($q) use ($from, $to) {
                    $q->whereIn('status', ['paid']) // exclude refunded
                      ->whereBetween('paid_at', [$from, $to]);
                })
                ->join('payments', 'bookings.id', '=', 'payments.booking_id')
                ->select(
                    DB::raw('DATE(payments.paid_at) as date'),
                    DB::raw('SUM(bookings.total) as gmv'),
                    DB::raw('SUM(bookings.platform_fee) as commission'),
                    DB::raw('COUNT(bookings.id) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date')
                ->toArray();
        });
    }

    public function topVendors(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return Booking::whereHas('payment', function ($q) use ($from, $to) {
                $q->whereIn('status', ['paid']) // exclude refunded
                  ->whereBetween('paid_at', [$from, $to]);
            })
            ->join('vendors', 'bookings.vendor_id', '=', 'vendors.id')
            ->select(
                'vendors.id',
                'vendors.business_name',
                DB::raw('COUNT(bookings.id) as total_bookings'),
                DB::raw('SUM(bookings.total) as gmv'),
                DB::raw('SUM(bookings.platform_fee) as commission')
            )
            ->groupBy('vendors.id', 'vendors.business_name')
            ->orderByDesc('gmv')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function topCars(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return Booking::whereHas('payment', function ($q) use ($from, $to) {
                $q->whereIn('status', ['paid']) // exclude refunded
                  ->whereBetween('paid_at', [$from, $to]);
            })
            ->join('cars', 'bookings.car_id', '=', 'cars.id')
            ->select(
                'cars.id',
                DB::raw("CONCAT(cars.brand, ' ', cars.model) as car_name"),
                DB::raw('COUNT(bookings.id) as total_bookings'),
                DB::raw('SUM(bookings.total) as revenue')
            )
            ->groupBy('cars.id', 'cars.brand', 'cars.model')
            ->orderByDesc('total_bookings')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function bookingsByStatus(): array
    {
        return Booking::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Breakdown per vendor: booking, subtotal, komisi platform, payout vendor.
     * Exclude refunded payments untuk akurasi laporan.
     */
    public function vendorBreakdown(Carbon $from, Carbon $to): array
    {
        return Booking::whereHas('payment', function ($q) use ($from, $to) {
                $q->whereIn('status', ['paid']) // exclude refunded
                  ->whereBetween('paid_at', [$from, $to]);
            })
            ->join('vendors', 'bookings.vendor_id', '=', 'vendors.id')
            ->select(
                'vendors.id',
                'vendors.business_name',
                DB::raw('COUNT(bookings.id) as total_bookings'),
                DB::raw('SUM(bookings.subtotal) as total_subtotal'),
                DB::raw('SUM(bookings.platform_fee) as total_commission'),
                DB::raw('SUM(bookings.vendor_payout_amount) as total_payout'),
                DB::raw('SUM(bookings.total) as total_gmv')
            )
            ->groupBy('vendors.id', 'vendors.business_name')
            ->orderByDesc('total_gmv')
            ->get()
            ->toArray();
    }
}
