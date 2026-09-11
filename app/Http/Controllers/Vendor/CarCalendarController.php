<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CarCalendarController extends Controller
{
    public function __construct(private CarAvailabilityService $service) {}

    public function show(Car $car, Request $request)
    {
        // Pastikan mobil milik vendor yang login
        $vendor = auth('vendor')->user()?->vendor;
        abort_unless($vendor && $car->vendor_id === $vendor->id, 403);

        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $calendar = $this->service->getMonthCalendar($car, $year, $month);
        $car->load(['pricing', 'category']);

        return view('vendor.cars.calendar', compact('car', 'calendar', 'year', 'month'));
    }

    public function block(Request $request, Car $car)
    {
        $vendor = auth('vendor')->user()?->vendor;
        abort_unless($vendor && $car->vendor_id === $vendor->id, 403);

        $validated = $request->validate([
            'from'   => ['required', 'date', 'after_or_equal:today'],
            'to'     => ['required', 'date', 'after_or_equal:from'],
            'reason' => ['nullable', 'string', 'max:200'],
            'type'   => ['required', 'in:blocked,maintenance'],
        ]);

        if ($validated['type'] === 'maintenance') {
            $this->service->setMaintenance($car, Carbon::parse($validated['from']), Carbon::parse($validated['to']), $validated['reason'] ?? '');
        } else {
            $this->service->block($car, Carbon::parse($validated['from']), Carbon::parse($validated['to']), $validated['reason'] ?? '');
        }

        return back()->with('success', 'Tanggal berhasil diblokir.');
    }

    public function destroy(Request $request, Car $car)
    {
        $vendor = auth('vendor')->user()?->vendor;
        abort_unless($vendor && $car->vendor_id === $vendor->id, 403);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $this->service->unblock($car, Carbon::parse($validated['from']), Carbon::parse($validated['to']));

        return back()->with('success', 'Blokir tanggal berhasil dihapus.');
    }
}
