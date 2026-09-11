<?php

namespace App\Notifications\Subscription;

use App\Models\VendorSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VendorSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $package = $this->subscription->package;

        return (new MailMessage)
            ->subject("Paket {$package->name} Dikunci — Pilih Paket Baru")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Paket **{$package->name}** Anda telah dikunci karena melewati grace period.")
            ->line("Mobil Anda **tidak tampil di pencarian** sampai Anda memilih paket baru.")
            ->line("Booking yang sedang berjalan tetap dilanjutkan hingga selesai.")
            ->action('Pilih Paket Sekarang', url('/vendor/plans'))
            ->line('Segera pilih paket untuk mengaktifkan kembali akun vendor Anda.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'subscription_expired_locked',
            'subscription_id' => $this->subscription->id,
            'package'         => $this->subscription->package->name,
            'message'         => 'Paket Anda dikunci. Pilih paket baru untuk melanjutkan.',
            'url'             => '/vendor/plans',
        ];
    }
}
