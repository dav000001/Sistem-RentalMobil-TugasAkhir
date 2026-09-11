<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function handle(): void
    {
        // Send reminder 1 day before pickup
        if ($this->booking->status === 'confirmed') {
            try {
                $this->booking->customer?->user?->notify(
                    new BookingReminderNotification($this->booking)
                );
            } catch (\Throwable $e) {
                \Log::error('SendBookingReminderJob failed for booking ' . $this->booking->id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
