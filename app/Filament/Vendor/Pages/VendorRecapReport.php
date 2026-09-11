<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Booking;
use App\Models\Review;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class VendorRecapReport extends Page
{
    protected static ?string $navigationLabel = 'Rekap Transaksi';
    protected static ?int    $navigationSort  = 6;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    public function getView(): string
    {
        return 'filament.vendor.pages.vendor-recap-report';
    }

    #[Url]
    public string $period    = 'monthly';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to   = '';

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_to   = now()->endOfMonth()->toDateString();
    }

    public function applyPeriod(): void
    {
        match ($this->period) {
            'daily'   => [$this->date_from = now()->toDateString(),                $this->date_to = now()->toDateString()],
            'monthly' => [$this->date_from = now()->startOfMonth()->toDateString(), $this->date_to = now()->endOfMonth()->toDateString()],
            'yearly'  => [$this->date_from = now()->startOfYear()->toDateString(),  $this->date_to = now()->endOfYear()->toDateString()],
            default   => null,
        };
    }

    protected function getVendor(): ?\App\Models\Vendor
    {
        return auth('vendor')->user()?->vendor;
    }

    public function getStats(): array
    {
        $vendor = $this->getVendor();
        if (!$vendor) return [];

        $from = Carbon::parse($this->date_from ?: now()->startOfMonth())->startOfDay();
        $to   = Carbon::parse($this->date_to   ?: now()->endOfMonth())->endOfDay();

        $q = Booking::where('vendor_id', $vendor->id)->whereBetween('created_at', [$from, $to]);

        return [
            'total_bookings' => (clone $q)->count(),
            'completed'      => (clone $q)->where('status', 'completed')->count(),
            'cancelled'      => (clone $q)->where('status', 'cancelled')->count(),
            'ongoing'        => (clone $q)->whereIn('status', ['confirmed', 'ongoing'])->count(),
            'late_count'     => (clone $q)->where('is_late', true)->count(),
            'total_revenue'  => (clone $q)->where('status', 'completed')->sum('vendor_payout_amount'),
            'total_late_fee' => (clone $q)->where('is_late', true)->sum('late_fee'),
            'avg_rating'     => round(
                Review::whereHas('booking', fn ($r) => $r->where('vendor_id', $vendor->id))
                    ->whereBetween('created_at', [$from, $to])
                    ->avg('rating') ?? 0, 1
            ),
        ];
    }

    public function getBookingList(): \Illuminate\Support\Collection
    {
        $vendor = $this->getVendor();
        if (!$vendor) return collect();

        $from = Carbon::parse($this->date_from ?: now()->startOfMonth())->startOfDay();
        $to   = Carbon::parse($this->date_to   ?: now()->endOfMonth())->endOfDay();

        return Booking::with(['customer', 'car'])
            ->where('vendor_id', $vendor->id)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getLateBookings(): \Illuminate\Support\Collection
    {
        $vendor = $this->getVendor();
        if (!$vendor) return collect();

        $from = Carbon::parse($this->date_from ?: now()->startOfMonth())->startOfDay();
        $to   = Carbon::parse($this->date_to   ?: now()->endOfMonth())->endOfDay();

        return Booking::with(['customer', 'car'])
            ->where('vendor_id', $vendor->id)
            ->where('is_late', true)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->orderByDesc('completed_at')
            ->get();
    }

    // ── Export Methods dihapus — ditangani RecapExportController via route ──
    // GET /vendor/recap/export-pdf?from=&to=
    // GET /vendor/recap/export-csv?from=&to=
}
