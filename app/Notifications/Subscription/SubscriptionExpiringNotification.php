<?php

namespace App\Notifications\Subscription;

use App\Models\VendorSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public VendorSubscription $subscription,
        public int $daysLeft
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $package = $this->subscription->package;
        $expiry  = $this->subscription->expires_at?->format('d M Y') ?? '-';
        $days    = $this->daysLeft;

        return (new MailMessage)
            ->subject("Paket {$package->name} Berakhir dalam {$days} Hari")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Paket **{$package->name}** Anda akan berakhir pada **{$expiry}** ({$days} hari lagi).")
            ->line("Biaya perpanjangan: **Rp " . number_format($package->price_per_month, 0, ',', '.') . "/bulan**")
            ->line("Perpanjang sekarang agar layanan tidak terputus.")
            ->action('Perpanjang Paket', url('/vendor/plans'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'package'         => $this->subscription->package->name,
            'days_left'       => $this->daysLeft,
            'expires_at'      => $this->subscription->expires_at?->toDateString(),
            'message'         => "Paket {$this->subscription->package->name} berakhir dalam {$this->daysLeft} hari.",
            'url'             => '/vendor/plans',
        ];
    }
}
