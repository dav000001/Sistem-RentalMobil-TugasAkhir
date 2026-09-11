<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Customer;
use App\Models\Review;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::with('user')->get();
        $cars = Car::with('vendor')->get();

        if ($customers->isEmpty() || $cars->isEmpty()) {
            return;
        }

        $reviews = [
            ['rating' => 5, 'comment' => 'Mobil sangat bersih dan terawat. Vendor ramah dan responsif. Sangat direkomendasikan!'],
            ['rating' => 5, 'comment' => 'Pengalaman sewa yang menyenangkan. Mobil sesuai foto, kondisi prima.'],
            ['rating' => 4, 'comment' => 'Pelayanan bagus, mobil nyaman. Proses serah terima cepat dan mudah.'],
            ['rating' => 4, 'comment' => 'Harga terjangkau, kualitas oke. Akan sewa lagi di lain waktu.'],
            ['rating' => 5, 'comment' => 'Top! Mobil bersih, AC dingin, vendor tepat waktu. Puas banget!'],
            ['rating' => 4, 'comment' => 'Oke lah, sesuai ekspektasi. Mobil dalam kondisi baik.'],
            ['rating' => 5, 'comment' => 'Sangat memuaskan! Vendor profesional dan mobil terawat dengan baik.'],
            ['rating' => 3, 'comment' => 'Cukup baik, tapi ada sedikit keterlambatan saat pengambilan mobil.'],
        ];

        $reviewIndex = 0;

        foreach ($cars as $car) {
            // Buat 2 review per mobil
            for ($i = 0; $i < 2; $i++) {
                $customer = $customers[$reviewIndex % $customers->count()];
                $reviewData = $reviews[$reviewIndex % count($reviews)];

                // Buat booking dummy yang sudah completed
                $booking = Booking::create([
                    'code'                 => 'BK-DEMO-' . strtoupper(uniqid()),
                    'customer_id'          => $customer->id,
                    'car_id'               => $car->id,
                    'vendor_id'            => $car->vendor_id,
                    'start_at'             => now()->subDays(rand(10, 60)),
                    'end_at'               => now()->subDays(rand(1, 9)),
                    'with_driver'          => false,
                    'pickup_location'      => 'Demo Location',
                    'subtotal'             => $car->pricing->daily_price * 2,
                    'addon_fees'           => 0,
                    'discount'             => 0,
                    'total'                => $car->pricing->daily_price * 2,
                    'platform_fee'         => $car->pricing->daily_price * 2 * 0.10,
                    'vendor_payout_amount' => $car->pricing->daily_price * 2 * 0.90,
                    'status'               => 'completed',
                ]);

                Review::create([
                    'booking_id'  => $booking->id,
                    'customer_id' => $customer->id,
                    'vendor_id'   => $car->vendor_id,
                    'car_id'      => $car->id,
                    'rating'      => $reviewData['rating'],
                    'comment'     => $reviewData['comment'],
                ]);

                $reviewIndex++;
            }
        }
    }
}
