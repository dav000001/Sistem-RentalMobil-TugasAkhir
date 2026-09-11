<?php

namespace App\Jobs;

use App\Models\VendorSubscription;
use App\Notifications\Subscription\SubscriptionExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReminderRenewSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $reminders = [7, 3, 1];

        foreach ($reminders as $days) {
            VendorSubscription::where('status', 'active')
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', now()->addDays($days)->toDateString())
                ->with(['vendor.user', 'package'])
                ->cursor()
                ->each(function (VendorSubscription $sub) use ($days) {
                    if (!$sub->vendor?->user) return;
                    $sub->vendor->user->notify(
                        new SubscriptionExpiringNotification($sub, $days)
                    );
                });
        }
    }
}
