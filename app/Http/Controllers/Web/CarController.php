<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CarAvailabilityService;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function show(Car $car, Request $request)
    {
        abort_unless($car->isPublished() || $car->status === 'unavailable', 404);

        $car->load([
            'vendor.user',
            'vendor.city',
            'vendor.currentSubscription.package',
            'vendor.drivers' => fn ($q) => $q->where('status', 'active'),
            'photos',
            'pricing',
            'city',
            'category',
        ]);

        $reviews = $car->reviews()
            ->with('customer.user')
            ->latest()
            ->paginate(5);

        $relatedCars = Car::query()
            ->where('status', 'published')
            ->where('city_id', $car->city_id)
            ->where('id', '!=', $car->id)
            ->with(['photos', 'pricing'])
            ->withAvg('reviews', 'rating')
            ->take(4)
            ->get();

        $defaultPeriod = [
            'start_at' => $request->date('start_at') ?? now()->addDay()->setTime(10, 0),
            'end_at'   => $request->date('end_at')   ?? now()->addDays(2)->setTime(10, 0),
        ];

        // Cek ketersediaan berdasarkan tanggal yang dipilih jika ada di query string
        // null  = tidak ada filter tanggal → tampilkan status statis (activeBooking)
        // true  = tersedia di tanggal tersebut
        // false = tidak tersedia di tanggal tersebut
        $dateAvailability = null;
        if ($request->filled('start_at') && $request->filled('end_at')) {
            try {
                $checkStart = \Carbon\Carbon::parse($request->start_at);
                $checkEnd   = \Carbon\Carbon::parse($request->end_at);
                if ($checkEnd->gt($checkStart)) {
                    $service = app(CarAvailabilityService::class);
                    $dateAvailability = !$car->isCurrentlyUnavailable()
                        && $service->isAvailable($car, $checkStart, $checkEnd);
                }
            } catch (\Throwable) {
                $dateAvailability = null;
            }
        }

        return view('public.cars.show', compact(
            'car', 'reviews', 'relatedCars', 'defaultPeriod', 'dateAvailability'
        ));
    }
}
