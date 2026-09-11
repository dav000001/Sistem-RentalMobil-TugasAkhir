<?php

namespace App\Console\Commands;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send reminders for bookings starting tomorrow';

    public function handle()
    {
        $tomorrow = now()->addDay()->startOfDay();
        $endOfTomorrow = now()->addDay()->endOfDay();

        $bookings = Booking::where('status', 'confirmed')
            ->whereBetween('start_at', [$tomorrow, $endOfTomorrow])
            ->get();

        foreach ($bookings as $booking) {
            SendBookingReminderJob::dispatch($booking);
        }

        $this->info("Sent {$bookings->count()} booking reminders");
    }
}
