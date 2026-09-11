<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $cities = Cache::remember('cities.popular', 600, function () {
                return City::withCount(['cars' => function ($q) {
                    $q->where('status', 'published')
                      ->whereHas('vendor', fn ($v) => $v->where('status', 'approved'));
                }])
                    ->having('cars_count', '>', 0)
                    ->orderByDesc('cars_count')
                    ->take(8)
                    ->get();
            });
        } catch (\Throwable $e) {
            $cities = collect();
        }

        $categories = Category::all();

        try {
            $featuredCars = Car::query()
                ->where('cars.status', 'published')
                ->with(['vendor.user', 'vendor.currentSubscription.package', 'photos', 'pricing', 'city', 'category',
                        'bookings' => fn($q) => $q->whereIn('status', ['awaiting_vendor','confirmed','ongoing'])->where('end_at', '>=', now())])
                ->withAvg('reviews', 'rating')
                ->leftJoin('vendors as v_feat', 'cars.vendor_id', '=', 'v_feat.id')
                ->leftJoin('vendor_subscriptions as vs_feat', 'v_feat.current_subscription_id', '=', 'vs_feat.id')
                ->leftJoin('subscription_packages as sp_feat', 'vs_feat.package_id', '=', 'sp_feat.id')
                ->where('v_feat.status', 'approved')
                ->select('cars.*')
                ->orderByRaw('COALESCE(sp_feat.rank, 0) DESC')
                ->orderByRaw('(SELECT AVG(r.rating) FROM reviews r WHERE r.car_id = cars.id) DESC')
                ->take(20)
                ->get();
        } catch (\Throwable $e) {
            $featuredCars = collect();
        }

        return view('public.home', compact('cities', 'categories', 'featuredCars'));
    }
}
