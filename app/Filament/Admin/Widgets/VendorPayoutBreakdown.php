<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\Vendor;
use Filament\Widgets\Widget;

class VendorPayoutBreakdown extends Widget
{
    protected string $view = 'filament.admin.widgets.vendor-payout-breakdown';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        // Semua vendor yang punya booking sudah dibayar (tanpa filter bulan)
        $vendorIds = Booking::whereHas('payment', fn ($q) =>
            $q->where('status', 'paid') // exclude refunded
        )->pluck('vendor_id')->unique();

        $vendors = Vendor::whereIn('id', $vendorIds)->get()->map(function ($vendor) {
            // Semua booking paid vendor ini — EXCLUDE yang sudah di-refund
            $bookings = Booking::where('vendor_id', $vendor->id)
                ->whereHas('payment', fn ($q) => $q->where('status', 'paid')) // exclude refunded
                ->get();

            $totalMasuk        = $bookings->sum('total');
            $komisi            = $bookings->sum('platform_fee');
            $totalVendorPayout = $bookings->sum('vendor_payout_amount');
            $jumlahBooking     = $bookings->count();

            // Sudah di-payout (semua waktu)
            $sudahDibayar = Payout::where('vendor_id', $vendor->id)
                ->where('status', 'paid')
                ->sum('amount');

            // Payout pending
            $pending = Payout::where('vendor_id', $vendor->id)
                ->where('status', 'pending')
                ->sum('amount');

            // Sisa yang belum dibuatkan payout
            $sisa = max(0, $totalVendorPayout - $sudahDibayar - $pending);

            return [
                'id'             => $vendor->id,
                'nama'           => $vendor->business_name,
                'bank'           => $vendor->bank_name . ' · ' . $vendor->bank_account_number . ' a/n ' . $vendor->bank_account_name,
                'paket'          => strtoupper($vendor->plan->value),
                'komisi_pct'     => ($vendor->getCommissionRate() * 100) . '%',
                'jumlah_booking' => $jumlahBooking,
                'total_masuk'    => $totalMasuk,
                'komisi'         => $komisi,
                'harus_dibayar'  => $totalVendorPayout,
                'sudah_dibayar'  => $sudahDibayar,
                'pending'        => $pending,
                'sisa'           => $sisa,
            ];
        })
        // Hanya tampilkan vendor yang masih ada sisa belum di-payout atau pending
        ->filter(fn ($v) => $v['sisa'] > 0 || $v['pending'] > 0)
        ->values();

        return ['vendors' => $vendors];
    }
}
