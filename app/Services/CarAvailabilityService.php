<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarAvailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class CarAvailabilityService
{
    /**
     * Cek apakah mobil tersedia di rentang tanggal tertentu
     */
    public function isAvailable(Car $car, Carbon $start, Carbon $end): bool
    {
        // Cek booking aktif yang overlap
        $hasBooking = $car->bookings()
            ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                  ->orWhereBetween('end_at', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_at', '<=', $start)
                         ->where('end_at', '>=', $end);
                  });
            })->exists();

        if ($hasBooking) return false;

        // Cek blocked/maintenance
        $hasBlock = CarAvailability::where('car_id', $car->id)
            ->whereIn('status', ['blocked', 'maintenance'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->exists();

        return !$hasBlock;
    }

    /**
     * Ambil semua tanggal yang tidak tersedia dalam rentang
     */
    public function getUnavailableDates(Car $car, Carbon $from, Carbon $to): array
    {
        $unavailable = [];

        // Dari bookings aktif
        $bookings = $car->bookings()
            ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
            ->where('end_at', '>=', $from)
            ->where('start_at', '<=', $to)
            ->get(['start_at', 'end_at']);

        foreach ($bookings as $booking) {
            $period = CarbonPeriod::create($booking->start_at, $booking->end_at);
            foreach ($period as $date) {
                $unavailable[] = $date->toDateString();
            }
        }

        // Dari blocked/maintenance
        $blocks = CarAvailability::where('car_id', $car->id)
            ->whereIn('status', ['blocked', 'maintenance'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        return array_unique(array_merge($unavailable, $blocks));
    }

    /**
     * Block tanggal oleh vendor
     */
    public function block(Car $car, Carbon $from, Carbon $to, string $reason = ''): void
    {
        $period = CarbonPeriod::create($from, $to);
        foreach ($period as $date) {
            CarAvailability::updateOrCreate(
                ['car_id' => $car->id, 'date' => $date->toDateString()],
                ['status' => 'blocked', 'reason' => $reason]
            );
        }
    }

    /**
     * Set maintenance
     */
    public function setMaintenance(Car $car, Carbon $from, Carbon $to, string $reason = ''): void
    {
        $period = CarbonPeriod::create($from, $to);
        foreach ($period as $date) {
            CarAvailability::updateOrCreate(
                ['car_id' => $car->id, 'date' => $date->toDateString()],
                ['status' => 'maintenance', 'reason' => $reason]
            );
        }
    }

    /**
     * Hapus block/maintenance
     */
    public function unblock(Car $car, Carbon $from, Carbon $to): void
    {
        CarAvailability::where('car_id', $car->id)
            ->whereIn('status', ['blocked', 'maintenance'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->delete();
    }

    /**
     * Ambil status kalender untuk bulan tertentu
     */
    public function getMonthCalendar(Car $car, int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to   = Carbon::create($year, $month, 1)->endOfMonth();

        $calendar = [];

        // Isi semua hari dengan 'available'
        $period = CarbonPeriod::create($from, $to);
        foreach ($period as $date) {
            $calendar[$date->toDateString()] = 'available';
        }

        // Tandai booked dari bookings
        $bookings = $car->bookings()
            ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
            ->where('end_at', '>=', $from)
            ->where('start_at', '<=', $to)
            ->get(['start_at', 'end_at', 'id']);

        foreach ($bookings as $booking) {
            $bPeriod = CarbonPeriod::create(
                max($booking->start_at, $from),
                min($booking->end_at, $to)
            );
            foreach ($bPeriod as $date) {
                $calendar[$date->toDateString()] = 'booked';
            }
        }

        // Tandai blocked/maintenance
        $blocks = CarAvailability::where('car_id', $car->id)
            ->whereIn('status', ['blocked', 'maintenance'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'status', 'reason']);

        foreach ($blocks as $block) {
            $calendar[$block->date] = $block->status;
        }

        return $calendar;
    }
}
