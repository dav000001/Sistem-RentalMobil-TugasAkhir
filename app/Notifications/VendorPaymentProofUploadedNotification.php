<?php

namespace App\Notifications;

use App\Models\VendorSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VendorPaymentProofUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(public VendorSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $vendor  = $this->subscription->vendor;
        $package = $this->subscription->package;

        return [
            'format'  => 'filament',
            'title'   => '💳 Bukti Bayar Paket Masuk - ' . ($vendor?->business_name ?? '-'),
            'body'    => ($vendor?->business_name ?? '-') . ' upload bukti transfer paket '
                       . ($package?->name ?? '-')
                       . ' — Rp ' . number_format($package?->price_per_month ?? 0, 0, ',', '.'),
            'icon'    => 'heroicon-o-credit-card',
            'color'   => 'warning',
            'url'     => '/admin/vendor-subscriptions',
        ];
    }
}
