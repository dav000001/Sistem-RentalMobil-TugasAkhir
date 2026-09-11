<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRecommendation;
use App\Models\Car;
use Illuminate\Support\Collection;

class CarRecommendationService
{
    /**
     * Cari mobil serupa untuk ditampilkan ke customer setelah booking ditolak vendor.
     *
     * Kriteria:
     * - Category sama
     * - City sama
     * - Bukan dari vendor yang menolak
     * - Status published
     * - Tersedia (tidak bentrok dengan periode booking asli)
     * - Prioritas: brand sama > category sama
     * - Limit 5 mobil
     * - Urut: rating vendor tertinggi, lalu harga termurah
     */
    public function findSimilarCars(Booking $rejectedBooking): Collection
    {
        $originalCar    = $rejectedBooking->car;
        $rejectedVendor = $rejectedBooking->vendor_id;
        $startAt        = $rejectedBooking->start_at;
        $endAt          = $rejectedBooking->end_at;

        if (!$originalCar) {
            return collect();
        }

        // Base query
        $query = Car::query()
            ->with(['vendor.user', 'pricing', 'photos', 'reviews', 'city', 'category'])
            ->where('status', 'published')
            ->where('city_id', $originalCar->city_id)
            ->where('category_id', $originalCar->category_id)
            ->where('vendor_id', '!=', $rejectedVendor)
            ->whereHas('pricing'); // Harus punya data pricing

        // Filter availability: tidak bentrok dengan booking aktif di periode yang sama
        $query->whereDoesntHave('bookings', function ($q) use ($startAt, $endAt) {
            $q->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
              ->where(function ($q2) use ($startAt, $endAt) {
                  $q2->whereBetween('start_at', [$startAt, $endAt])
                     ->orWhereBetween('end_at', [$startAt, $endAt])
                     ->orWhere(function ($q3) use ($startAt, $endAt) {
                         $q3->where('start_at', '<=', $startAt)
                            ->where('end_at', '>=', $endAt);
                     });
              });
        });

        // Prioritaskan brand sama (jika ada car_brand_id)
        if ($originalCar->car_brand_id) {
            $sameBrandCars = (clone $query)
                ->where('car_brand_id', $originalCar->car_brand_id)
                ->limit(5)
                ->get();

            if ($sameBrandCars->isNotEmpty()) {
                return $this->rankAndSort($sameBrandCars);
            }
        }

        // Fallback ke category sama (tanpa filter brand)
        $similarCars = $query->limit(10)->get(); // ambil 10 dulu untuk di-sort

        return $this->rankAndSort($similarCars)->take(5);
    }

    /**
     * Urutkan mobil berdasarkan:
     * 1. Rating vendor (avg reviews) tertinggi
     * 2. Harga per hari terendah
     */
    protected function rankAndSort(Collection $cars): Collection
    {
        return $cars->sortBy([
            // Rating vendor (desc) — vendor dengan review bagus lebih prioritas
            fn ($car) => -1 * ($car->vendor->reviews_avg_rating ?? 0),
            // Harga per hari (asc) — harga lebih murah lebih prioritas
            fn ($car) => $car->pricing->daily_price ?? 999999,
        ])->values();
    }

    /**
     * Simpan rekomendasi ke database untuk dokumentasi/analisis.
     */
    public function saveRecommendations(Booking $rejectedBooking, Collection $cars): void
    {
        foreach ($cars as $index => $car) {
            BookingRecommendation::create([
                'rejected_booking_id' => $rejectedBooking->id,
                'recommended_car_id'  => $car->id,
                'customer_id'         => $rejectedBooking->customer_id,
                'rank'                => $index + 1,
                'score'               => $this->calculateScore($rejectedBooking, $car),
            ]);
        }
    }

    /**
     * Hitung skor matching (opsional, untuk analisis).
     * Skor 0-100: brand match (+50), vendor rating (+30), harga kompetitif (+20)
     */
    protected function calculateScore(Booking $rejectedBooking, Car $car): float
    {
        $score = 0;

        // Brand sama = +50
        if ($car->car_brand_id && $car->car_brand_id === $rejectedBooking->car->car_brand_id) {
            $score += 50;
        }

        // Vendor rating tinggi = +30 (skala 0-5 jadi 0-30)
        $vendorRating = $car->vendor->reviews_avg_rating ?? 0;
        $score += ($vendorRating / 5) * 30;

        // Harga kompetitif = +20 (relatif terhadap harga booking asli)
        $originalPrice = $rejectedBooking->car->pricing->daily_price ?? 0;
        $recommendedPrice = $car->pricing->daily_price ?? 0;

        if ($originalPrice > 0) {
            $priceRatio = $recommendedPrice / $originalPrice;
            // Semakin murah semakin bagus, tapi tidak terlalu murah (quality indicator)
            if ($priceRatio >= 0.7 && $priceRatio <= 1.2) {
                $score += 20;
            } elseif ($priceRatio < 0.7) {
                $score += 10; // Terlalu murah, mungkin kualitas rendah
            }
        }

        return round($score, 2);
    }

    /**
     * Get rekomendasi yang sudah tersimpan untuk booking tertentu.
     */
    public function getRecommendations(Booking $rejectedBooking): Collection
    {
        return BookingRecommendation::where('rejected_booking_id', $rejectedBooking->id)
            ->with(['car.vendor.user', 'car.pricing', 'car.photos', 'car.city'])
            ->orderBy('rank')
            ->get();
    }
}
