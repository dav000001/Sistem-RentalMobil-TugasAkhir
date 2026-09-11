<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Kirim reminder booking H-1 setiap hari jam 09:00
Schedule::command('bookings:send-reminders')
    ->dailyAt('09:00')
    ->timezone('Asia/Jakarta');

// Proses payout mingguan setiap Senin jam 00:00
Schedule::command('payouts:process-weekly')
    ->weekly()
    ->mondays()
    ->at('00:00')
    ->timezone('Asia/Jakarta');

// Auto cancel booking yang belum bayar setelah 24 jam
Schedule::call(function () {
    \App\Models\Booking::where('status', 'awaiting_payment')
        ->where('created_at', '<', now()->subDay())
        ->each(function ($booking) {
            \App\Jobs\AutoCancelUnconfirmedBookingJob::dispatch($booking);
        });
})->hourly();

// Generate tagihan paket vendor 3 hari sebelum expired (setiap hari jam 08:00)
Schedule::command('vendor:plan-billing --generate')
    ->dailyAt('08:00')
    ->timezone('Asia/Jakarta');

// Kirim notifikasi paket mau habis (H-7, H-3, H-1, H+0) setiap hari jam 07:00
Schedule::command('vendor:plan-expiry-reminders')
    ->dailyAt('07:00')
    ->timezone('Asia/Jakarta');

// Downgrade vendor yang belum bayar setelah 7 hari grace period (setiap hari jam 09:00)
Schedule::command('vendor:plan-billing --expire')
    ->dailyAt('09:00')
    ->timezone('Asia/Jakarta');

// Expire subscriptions (active→grace, grace→expired_locked) — setiap jam jam 02:00
Schedule::job(new \App\Jobs\ExpireSubscriptionsJob)
    ->hourly()
    ->timezone('Asia/Jakarta');

// Reminder renew subscription (H-7, H-3, H-1) — setiap hari jam 09:00
Schedule::job(new \App\Jobs\ReminderRenewSubscriptionJob)
    ->dailyAt('09:00')
    ->timezone('Asia/Jakarta');

// Deteksi IDOR attempts — setiap hari jam 01:00
Schedule::job(new \App\Jobs\DetectIdorAttemptsJob)
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta');

// Auto mark booking confirmed → ongoing saat start_at sudah lewat (setiap jam)
Schedule::command('bookings:auto-mark-ongoing')
    ->hourly()
    ->timezone('Asia/Jakarta');

// Auto escalate disputes yang tidak ditangani (setiap hari jam 07:00)
Schedule::command('disputes:auto-escalate')
    ->dailyAt('07:00')
    ->timezone('Asia/Jakarta');

// Auto deteksi keterlambatan pengembalian (setiap 30 menit)
Schedule::command('bookings:auto-detect-late')
    ->everyThirtyMinutes()
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping(10)
    ->runInBackground();

// Bersihkan expired unavailability status (setiap hari jam 00:30)
Schedule::command('cars:clear-expired-unavailability')
    ->dailyAt('00:30')
    ->timezone('Asia/Jakarta');
