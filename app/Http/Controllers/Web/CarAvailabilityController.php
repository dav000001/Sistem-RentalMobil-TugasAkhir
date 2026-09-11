<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CarAvailabilityController extends Controller
{
    public function __construct(private CarAvailabilityService $service) {}

    /**
     * API endpoint untuk customer datepicker
     */
    public function show(Car $car, Request $request)
    {
        $from = Carbon::now();
        $to = Carbon::now()->addMonths(3);

        $unavailable = $this->service->getUnavailableDates($car, $from, $to);

        return response()->json([
            'car_id'      => $car->id,
            'unavailable' => array_values($unavailable),
        ]);
    }

    /**
     * Kalender vendor — tampilkan status per bulan
     */
    public function calendar(Car $car, Request $request)
    {
        // Pastikan mobil milik vendor yang login
        $vendor = auth()->user()->vendor;
        if (!$vendor || $car->vendor_id !== $vendor->id) {
            abort(403);
        }

        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $status = $this->service->getMonthStatus($car, $year, $month);
        $car->load(['pricing']);

        return view('vendor.cars.calendar', compact('car', 'status', 'year', 'month'));
    }

    /**
     * Block tanggal manual oleh vendor
     */
    public function block(Request $request, Car $car)
    {
        $vendor = auth()->user()->vendor;
        if (!$vendor || $car->vendor_id !== $vendor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'from'   => ['required', 'date', 'after_or_equal:today'],
            'to'     => ['required', 'date', 'after_or_equal:from'],
            'reason' => ['nullable', 'string', 'max:200'],
            'type'   => ['required', 'in:blocked,maintenance'],
        ]);

        $this->service->block(
            $car,
            Carbon::parse($validated['from']),
            Carbon::parse($validated['to']),
            $validated['reason'] ?? '',
            $validated['type']
        );

        return back()->with('success', 'Tanggal berhasil diblokir.');
    }

    /**
     * Hapus block manual
     */
    public function unblock(Request $request, Car $car)
    {
        $vendor = auth()->user()->vendor;
        if (!$vendor || $car->vendor_id !== $vendor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $this->service->unblock(
            $car,
            Carbon::parse($validated['from']),
            Carbon::parse($validated['to'])
        );

        return back()->with('success', 'Block tanggal berhasil dihapus.');
    }
}
