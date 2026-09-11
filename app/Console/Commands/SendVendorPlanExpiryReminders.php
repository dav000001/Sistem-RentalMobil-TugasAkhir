<?php

namespace App\Console\Commands;

use App\Enums\VendorPlan;
use App\Models\Vendor;
use App\Notifications\VendorPlanExpiringNotification;
use Illuminate\Console\Command;

class SendVendorPlanExpiryReminders extends Command
{
    protected $signature = 'vendor:plan-expiry-reminders';
    protected $description = 'Kirim notifikasi ke vendor yang paketnya akan segera berakhir';

    public function handle(): int
    {
        // Kirim pada H-7, H-3, H-1, dan H+0 (expired)
        $reminderDays = [7, 3, 1, 0];

        $sent = 0;

        foreach ($reminderDays as $days) {
            $targetDate = $days === 0
                ? now()->startOfDay()
                : now()->addDays($days)->startOfDay();

            $vendors = Vendor::whereIn('plan', [VendorPlan::Basic->value, VendorPlan::Premium->value])
                ->whereNotNull('plan_expires_at')
                ->whereDate('plan_expires_at', $targetDate->toDateString())
                ->with('user')
                ->get();

            foreach ($vendors as $vendor) {
                if (!$vendor->user) continue;

                $vendor->user->notify(
                    new VendorPlanExpiringNotification($vendor, $days)
                );

                $this->line("  → Notifikasi dikirim ke {$vendor->business_name} (H-{$days})");
                $sent++;
            }
        }

        $this->info("✅ {$sent} notifikasi berhasil dikirim.");

        return self::SUCCESS;
    }
}
