<?php

namespace App\Livewire;

use App\Models\Car;
use App\Services\CarAvailabilityService;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class SearchCars extends Component
{
    use WithPagination;

    public $city_id     = '';
    public $category_id = '';
    public $transmission = '';
    public $seats_min   = '';
    public $sort        = 'newest';
    public $start_at    = '';
    public $end_at      = '';

    protected $queryString = ['city_id', 'category_id', 'transmission', 'seats_min', 'sort', 'start_at', 'end_at'];

    public function updatingCityId()      { $this->resetPage(); }
    public function updatingCategoryId()  { $this->resetPage(); }
    public function updatingTransmission(){ $this->resetPage(); }
    public function updatingSeatsMin()    { $this->resetPage(); }
    public function updatingSort()        { $this->resetPage(); }
    public function updatingStartAt()     { $this->resetPage(); }
    public function updatingEndAt()       { $this->resetPage(); }

    public function render()
    {
        $query = Car::query()
            ->where('cars.status', 'published')
            ->with(['vendor.user', 'vendor.currentSubscription.package', 'photos', 'pricing', 'city', 'category',
                    'bookings' => fn($q) => $q->whereIn('status', ['awaiting_vendor','confirmed','ongoing'])->where('end_at', '>=', now())])
            ->withAvg('reviews', 'rating')
            ->leftJoin('vendors as v_priority', 'cars.vendor_id', '=', 'v_priority.id')
            ->leftJoin('vendor_subscriptions as vs_priority', 'v_priority.current_subscription_id', '=', 'vs_priority.id')
            ->leftJoin('subscription_packages as sp_priority', 'vs_priority.package_id', '=', 'sp_priority.id')
            ->whereNotNull('v_priority.current_subscription_id')
            ->whereIn('vs_priority.status', ['active', 'grace_period'])
            ->where('v_priority.status', 'approved')
            ->select('cars.*');

        if ($this->city_id) {
            $query->where('cars.city_id', $this->city_id);
        }
        if ($this->category_id) {
            $query->where('cars.category_id', $this->category_id);
        }
        if ($this->transmission) {
            $query->where('cars.transmission', $this->transmission);
        }
        if ($this->seats_min !== '' && $this->seats_min !== null) {
            $query->whereNotNull('cars.seats')
                  ->where('cars.seats', '>=', (int) $this->seats_min);
        }

        match ($this->sort) {
            'price_asc'  => $query->join('car_pricings', 'cars.id', '=', 'car_pricings.car_id')
                                  ->orderBy('car_pricings.daily_price', 'asc'),
            'price_desc' => $query->join('car_pricings', 'cars.id', '=', 'car_pricings.car_id')
                                  ->orderBy('car_pricings.daily_price', 'desc'),
            'rating'     => $query->orderByRaw('COALESCE(sp_priority.rank, 0) DESC')
                                  ->orderByRaw('(SELECT AVG(r.rating) FROM reviews r WHERE r.car_id = cars.id) DESC'),
            default      => $query->orderByRaw('COALESCE(sp_priority.rank, 0) DESC')
                                  ->latest('cars.created_at'),
        };

        $cars = $query->paginate(12);

        // ── Hitung availability map berdasarkan tanggal ──────────────
        $availabilityMap = [];
        $hasDateFilter   = filled($this->start_at) && filled($this->end_at);

        if ($hasDateFilter) {
            try {
                $searchStart = Carbon::parse($this->start_at)->startOfDay();
                $searchEnd   = Carbon::parse($this->end_at)->endOfDay();

                if ($searchEnd->gt($searchStart)) {
                    $service = app(CarAvailabilityService::class);
                    foreach ($cars as $car) {
                        $availabilityMap[$car->id] = !$car->isCurrentlyUnavailable()
                            && $service->isAvailable($car, $searchStart, $searchEnd);
                    }
                } else {
                    $hasDateFilter = false;
                }
            } catch (\Throwable) {
                $hasDateFilter = false;
            }
        }

        return view('livewire.search-cars', [
            'cars'            => $cars,
            'availabilityMap' => $availabilityMap,
            'hasDateFilter'   => $hasDateFilter,
        ]);
    }
}
