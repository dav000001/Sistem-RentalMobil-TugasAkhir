<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\CustomerRefund;
use Illuminate\Console\Command;

class FixExistingRefunds extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'booking:fix-existing-refunds';

    /**
     * The console command description.
     */
    protected $description = 'Buat CustomerRefund records untuk booking cancelled yang belum punya refund record';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mencari booking cancelled yang perlu dibuatkan refund record...');

        // Cari booking yang cancelled, payment paid, tapi belum ada refund record
        $bookings = Booking::where('status', 'cancelled')
            ->whereHas('payment', fn($q) => $q->where('status', 'paid'))
            ->whereDoesntHave('refund')
            ->with(['payment', 'customer'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('✅ Tidak ada booking yang perlu diperbaiki.');
            return;
        }

        $this->info("Ditemukan {$bookings->count()} booking yang perlu dibuatkan refund record.");

        $created = 0;
        foreach ($bookings as $booking) {
            try {
                $booking->createRefundIfNeeded();
                $created++;
                $this->line("✅ Refund record dibuat untuk booking {$booking->code}");
            } catch (\Exception $e) {
                $this->error("❌ Gagal membuat refund untuk booking {$booking->code}: {$e->getMessage()}");
            }
        }

        $this->info("✅ Selesai. {$created} refund record berhasil dibuat.");
    }
}