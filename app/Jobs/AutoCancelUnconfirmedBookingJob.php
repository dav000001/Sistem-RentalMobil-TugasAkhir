<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoCancelUnconfirmedBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function handle(): void
    {
        // Reload booking dari database untuk data terbaru
        $this->booking->refresh();

        // Hanya cancel jika:
        // 1. Status masih awaiting_payment
        // 2. Booking sudah dibuat lebih dari 24 jam yang lalu
        if (
            $this->booking->status === 'awaiting_payment' &&
            $this->booking->created_at->lt(now()->subHours(24))
        ) {
            $this->booking->update([
                'status'              => 'cancelled',
                'cancellation_reason' => 'Auto-cancel: tidak ada pembayaran dalam 24 jam',
                'cancelled_at'        => now(),
            ]);

            // Buat refund jika payment sudah paid (edge case: admin konfirmasi tapi status belum berubah)
            $this->booking->load('payment', 'customer');
            $this->booking->createRefundIfNeeded();

            // Notify customer
            try {
                $this->booking->customer->user->notify(
                    new \App\Notifications\BookingCancelledNotification($this->booking)
                );
            } catch (\Throwable) {}
        }
    }
}
