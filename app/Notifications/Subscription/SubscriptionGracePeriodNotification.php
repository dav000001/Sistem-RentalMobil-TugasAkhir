<?php

namespace App\Notifications\Subscription;

use App\Models\VendorSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionGracePeriodNotification extends Notification implements ShouldQueue
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
        $until   = $sub->grace_until?->format('d M Y') ?? '-';

        return (new MailMessage)
            ->subject("Paket {$package->name} Berakhir — Perpanjang Sekarang")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Paket **{$package->name}** Anda telah berakhir.")
            ->line("Anda masih bisa beroperasi dalam **grace period** hingga **{$until}** (7 hari).")
            ->line("Segera perpanjang paket agar mobil Anda tetap tampil di pencarian.")
            ->action('Perpanjang Paket', url('/vendor/plans'))
            ->line('Jika tidak diperpanjang, akun Anda akan dikunci setelah grace period berakhir.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'subscription_grace_period',
            'subscription_id' => $this->subscription->id,
            'package'         => $this->subscription->package->name,
            'grace_until'     => $this->subscription->grace_until?->toDateString(),
            'message'         => "Paket {$this->subscription->package->name} berakhir. Perpanjang dalam 7 hari.",
            'url'             => '/vendor/plans',
        ];
    }
}
