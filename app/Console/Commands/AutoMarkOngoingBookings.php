<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class AutoMarkOngoingBookings extends Command
{
    protected $signature   = 'bookings:auto-mark-ongoing';
    protected $description = 'Otomatis ubah booking confirmed → ongoing saat start_at sudah lewat';

    public function handle(): void
    {
        $bookings = Booking::where('status', 'confirmed')
            ->where('start_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            $booking->update([
                'status'      => 'ongoing',
                'picked_up_at' => $booking->picked_up_at ?? $booking->start_at,
            ]);
            $count++;
            $this->line("  → Booking {$booking->code} ditandai ongoing.");
        }

        $this->info("Selesai: {$count} booking diubah ke ongoing.");
    }
}
