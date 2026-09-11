<?php

namespace App\Notifications\Subscription;

use App\Models\VendorSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VendorSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sub     = $this->subscription;
        $package = $sub->package;
        $expiry  = $sub->expires_at?->format('d M Y') ?? 'Selamanya';

        return (new MailMessage)
            ->subject("Paket {$package->name} Aktif — Rental Mobil")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Paket **{$package->name}** Anda telah aktif.")
            ->line("Komisi platform: **{$package->commission_rate}%** per transaksi")
            ->line("Berlaku sampai: **{$expiry}**")
            ->action('Kelola Paket', url('/vendor/plans'))
            ->line('Terima kasih telah berlangganan!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'subscription_activated',
            'subscription_id' => $this->subscription->id,
            'package'         => $this->subscription->package->name,
            'expires_at'      => $this->subscription->expires_at?->toDateString(),
            'message'         => "Paket {$this->subscription->package->name} Anda telah aktif.",
            'url'             => '/vendor/plans',
        ];
    }
}
