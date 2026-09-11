<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Category;
use App\Models\City;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = Car::query()
            ->where('cars.status', 'published')
            ->with(['vendor.user', 'vendor.currentSubscription.package', 'photos', 'pricing', 'city', 'category',
                    'bookings' => fn($q) => $q->whereIn('status', ['awaiting_vendor','confirmed','ongoing'])->where('end_at', '>=', now())])
            ->withAvg('reviews', 'rating')
            // Join untuk prioritas pencarian berdasarkan paket vendor
            ->leftJoin('vendors as v_priority', 'cars.vendor_id', '=', 'v_priority.id')
            ->leftJoin('vendor_subscriptions as vs_priority', 'v_priority.current_subscription_id', '=', 'vs_priority.id')
            ->leftJoin('subscription_packages as sp_priority', 'vs_priority.package_id', '=', 'sp_priority.id')
            // Hanya tampilkan mobil dari vendor yang subscription-nya aktif atau grace period
            ->whereNotNull('v_priority.current_subscription_id')
            ->whereIn('vs_priority.status', ['active', 'grace_period'])
            // Hanya vendor yang masih approved (bukan suspended)
            ->where('v_priority.status', 'approved')
            ->select('cars.*');

        // Filter by city
        if ($request->filled('city_id')) {
            $query->where('cars.city_id', $request->city_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('cars.category_id', $request->category_id);
        }

        // Filter by transmission
        if ($request->filled('transmission')) {
            $query->where('cars.transmission', $request->transmission);
        }

        // Filter by seats
        if ($request->filled('seats_min')) {
            $seatsMin = (int) $request->seats_min;
            $query->whereNotNull('cars.seats')
                  ->where('cars.seats', '>=', $seatsMin);
        }

        // Sort
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->join('car_pricings', 'cars.id', '=', 'car_pricings.car_id')
                ->orderBy('car_pricings.daily_price', 'asc')
                ->groupBy('cars.id'),
            'price_desc' => $query->join('car_pricings', 'cars.id', '=', 'car_pricings.car_id')
                ->orderBy('car_pricings.daily_price', 'desc')
                ->groupBy('cars.id'),
            'rating' => $query->orderByRaw('COALESCE(sp_priority.rank, 0) DESC')
                ->orderByRaw('(SELECT AVG(r.rating) FROM reviews r WHERE r.car_id = cars.id) DESC'),
            default => $query->orderByRaw('COALESCE(sp_priority.rank, 0) DESC')
                ->latest('cars.created_at'),
        };

        $cars = $query->paginate(12)->withQueryString();
        $cities = City::orderBy('name')->get();
        $categories = Category::all();

        // ── Cek ketersediaan berdasarkan tanggal pencarian ──────────────
        // Jika customer mengisi tanggal, hitung availability per mobil
        $searchStart     = null;
        $searchEnd       = null;
        $availabilityMap = []; // car_id => bool

        if ($request->filled('start_at') && $request->filled('end_at')) {
            try {
                $searchStart = \Carbon\Carbon::parse($request->start_at);
                $searchEnd   = \Carbon\Carbon::parse($request->end_at);

                if ($searchEnd->gt($searchStart)) {
                    $service = app(\App\Services\CarAvailabilityService::class);
                    foreach ($cars as $car) {
                        $availabilityMap[$car->id] = !$car->isCurrentlyUnavailable()
                            && $service->isAvailable($car, $searchStart, $searchEnd);
                    }
                }
            } catch (\Throwable) {
                // Abaikan jika tanggal tidak valid
            }
        }

        return view('public.search', compact('cars', 'cities', 'categories', 'availabilityMap', 'searchStart', 'searchEnd'));
    }
}
