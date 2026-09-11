<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Vendor;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class RecapReport extends Page
{
    protected static ?string $navigationLabel  = 'Laporan Rekapitulasi';
    protected static ?int    $navigationSort   = 13;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar-square';
    }

    public function getView(): string
    {
        return 'filament.admin.pages.recap-report';
    }

    #[Url]
    public string $period     = 'monthly';

    #[Url]
    public string $date_from  = '';

    #[Url]
    public string $date_to    = '';

    public string $active_tab = 'vendor';

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

    private function getRange(): array
    {
        return [
            Carbon::parse($this->date_from ?: now()->startOfMonth())->startOfDay(),
            Carbon::parse($this->date_to   ?: now()->endOfMonth())->endOfDay(),
        ];
    }

    // ── Data Vendor ──────────────────────────────────────────────────
    public function getVendorRecapData(): \Illuminate\Support\Collection
    {
        [$from, $to] = $this->getRange();

        return Vendor::with(['cars', 'bookings' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->get()
            ->map(function (Vendor $vendor) use ($from, $to) {
                $bookings  = $vendor->bookings->whereBetween('created_at', [$from, $to]);
                $completed = $bookings->where('status', 'completed');

                return [
                    'id'            => $vendor->id,
                    'name'          => $vendor->business_name,
                    'total_cars'    => $vendor->cars->count(),
                    'total_bookings'=> $bookings->count(),
                    'completed'     => $completed->count(),
                    'total_revenue' => $completed->sum('vendor_payout_amount'),
                    'late_count'    => $bookings->where('is_late', true)->count(),
                    'avg_rating'    => round(
                        \App\Models\Review::whereHas('booking', fn ($q) => $q->where('vendor_id', $vendor->id))
                            ->whereBetween('created_at', [$from, $to])
                            ->avg('rating') ?? 0, 1
                    ),
                ];
            })
            ->sortByDesc('total_revenue');
    }

    // ── Data Customer ────────────────────────────────────────────────
    public function getCustomerRecapData(): \Illuminate\Support\Collection
    {
        [$from, $to] = $this->getRange();

        return Customer::with(['bookings' => fn ($q) => $q->whereBetween('created_at', [$from, $to])->with('car')])
            ->get()
            ->filter(fn ($c) => $c->bookings->isNotEmpty())
            ->map(function (Customer $customer) {
                $bookings  = $customer->bookings;
                $completed = $bookings->where('status', 'completed');

                $favCar     = $bookings->groupBy('car_id')->sortByDesc(fn ($g) => $g->count())->keys()->first();
                $favCarName = $bookings->firstWhere('car_id', $favCar)?->car
                    ? ($bookings->firstWhere('car_id', $favCar)->car->brand . ' ' . $bookings->firstWhere('car_id', $favCar)->car->model)
                    : '—';

                return [
                    'id'             => $customer->id,
                    'name'           => $customer->full_name,
                    'email'          => $customer->user?->email,
                    'total_bookings' => $bookings->count(),
                    'completed'      => $completed->count(),
                    'total_spent'    => $completed->sum('total'),
                    'late_count'     => $bookings->where('is_late', true)->count(),
                    'fav_car'        => $favCarName,
                ];
            })
            ->sortByDesc('total_spent');
    }

    // ── Summary Stats ────────────────────────────────────────────────
    public function getSummaryStats(): array
    {
        [$from, $to] = $this->getRange();
        $q = Booking::whereBetween('created_at', [$from, $to]);

        return [
            'total_bookings'   => (clone $q)->count(),
            'completed'        => (clone $q)->where('status', 'completed')->count(),
            'cancelled'        => (clone $q)->where('status', 'cancelled')->count(),
            'late_returns'     => (clone $q)->where('is_late', true)->count(),
            'total_revenue'    => (clone $q)->where('status', 'completed')->sum('total'),
            'platform_fee'     => (clone $q)->where('status', 'completed')->sum('platform_fee'),
            'active_vendors'   => Vendor::whereHas('bookings', fn ($q) => $q->whereBetween('created_at', [$from, $to]))->count(),
            'active_customers' => Customer::whereHas('bookings', fn ($q) => $q->whereBetween('created_at', [$from, $to]))->count(),
        ];
    }

    // ── Export Methods dihapus — ditangani RecapExportController via route ──
    // GET /admin/recap/vendor-pdf?from=&to=
    // GET /admin/recap/vendor-csv?from=&to=
    // GET /admin/recap/customer-pdf?from=&to=
    // GET /admin/recap/customer-csv?from=&to=
}
