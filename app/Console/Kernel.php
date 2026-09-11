<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Clear expired unavailability status (daily at 00:30)
        $schedule->command('cars:clear-expired-unavailability')
            ->dailyAt('00:30')
            ->timezone('Asia/Jakarta');

        // Send booking reminders (daily at 09:00)
        $schedule->command('bookings:send-reminders')
            ->dailyAt('09:00')
            ->timezone('Asia/Jakarta');

        // Process weekly payouts (every Monday at 00:00)
        $schedule->command('payouts:process-weekly')
            ->weekly()
            ->mondays()
            ->at('00:00')
            ->timezone('Asia/Jakarta');

        // Send vendor plan expiry reminders (daily at 08:00)
        $schedule->command('vendor:send-plan-expiry-reminders')
            ->dailyAt('08:00')
            ->timezone('Asia/Jakarta');

        // Process vendor plan billing (daily at 01:00)
        $schedule->command('vendor:process-plan-billing')
            ->dailyAt('01:00')
            ->timezone('Asia/Jakarta');

        // Vendor isolation check (every 6 hours)
        $schedule->command('vendor:isolation-check')
            ->everySixHours()
            ->timezone('Asia/Jakarta');

        // Auto cancel unpaid bookings after 24 hours (every hour)
        $schedule->call(function () {
            $bookings = \App\Models\Booking::where('status', 'awaiting_payment')
                ->where('created_at', '<', now()->subDay())
                ->get();
            
            foreach ($bookings as $booking) {
                \App\Jobs\AutoCancelUnconfirmedBookingJob::dispatch($booking);
            }
        })->hourly();

        // Auto mark booking confirmed → ongoing saat start_at sudah lewat (setiap jam)
        $schedule->command('bookings:auto-mark-ongoing')
            ->hourly()
            ->timezone('Asia/Jakarta');

        // Auto-escalate disputes tidak ditangani dalam 3 hari (setiap hari pukul 07:00)
        $schedule->command('disputes:auto-escalate')
            ->dailyAt('07:00')
            ->timezone('Asia/Jakarta');

        // Auto detect late returns (every 30 minutes)
        $schedule->command('bookings:auto-detect-late')
            ->everyThirtyMinutes()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping(10) // Prevent overlapping — max 10 min lock
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}