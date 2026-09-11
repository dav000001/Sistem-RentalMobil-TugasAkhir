<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\ReturnReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendReturnRemindersCommand extends Command
{
    protected $signature = 'app:send-return-reminders';
    protected $description = 'Kirim notifikasi pengingat pengembalian ke customer 2 jam sebelum waktu sewa berakhir.';

    public function handle(): int
    {
        $now = now();
        $targetWindow = (clone $now)->addHours(2);

        // Ambil booking ongoing yang akan jatuh tempo dalam 2 jam ke depan
        $bookings = Booking::where('status', 'ongoing')
            ->where('end_at', '>', $now)
            ->where('end_at', '<=', $targetWindow)
            ->with(['customer.user', 'car'])
            ->get();

        $sentCount = 0;

        foreach ($bookings as $booking) {
            $cacheKey = 'return_reminder_sent_' . $booking->id;

            if (Cache::has($cacheKey)) {
                continue;
            }

            try {
                $booking->customer?->user?->notify(new ReturnReminderNotification($booking));
                Cache::put($cacheKey, true, now()->addDays(2));
                $sentCount++;

                $this->info("Reminder terkirim ke customer booking: {$booking->code}");
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim reminder untuk booking {$booking->code}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Proses pengingat selesai. Total terkirim: {$sentCount}");
        return Command::SUCCESS;
    }
}
