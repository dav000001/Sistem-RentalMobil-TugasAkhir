<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SuspendedNotification extends Notification
{
    use Queueable;

    public function __construct(public Vendor $vendor, public string $reason) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Vendor Dibekukan')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Akun vendor ' . $this->vendor->business_name . ' telah dibekukan sementara.')
            ->line('Alasan: ' . $this->reason)
            ->line('Hubungi support untuk informasi lebih lanjut.');
    }

    public function toArray($notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'message'   => 'Akun dibekukan: ' . $this->reason,
        ];
    }
}
