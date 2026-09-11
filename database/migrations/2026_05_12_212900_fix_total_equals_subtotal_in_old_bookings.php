<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix booking lama yang total = subtotal + platform_fee (logika lama)
        // Seharusnya total = subtotal (customer bayar harga asli, komisi dibebankan ke vendor)
        DB::table('bookings')
            ->whereRaw('total > subtotal')
            ->orderBy('id')
            ->each(function ($booking) {
                // Hanya fix jika selisih = platform_fee (memastikan ini data lama)
                if (abs(($booking->total - $booking->subtotal) - $booking->platform_fee) < 1) {
                    DB::table('bookings')
                        ->where('id', $booking->id)
                        ->update(['total' => $booking->subtotal]);

                    // Update juga payment amount agar konsisten
                    DB::table('payments')
                        ->where('booking_id', $booking->id)
                        ->update(['amount' => $booking->subtotal]);
                }
            });
    }

    public function down(): void
    {
        // Tidak bisa di-rollback karena data asli sudah tidak ada
    }
};
