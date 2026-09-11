<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UnsuspendedNotification extends Notification
{
    use Queueable;

    public function __construct(public Vendor $vendor) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Vendor Aktif Kembali')
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Akun vendor ' . $this->vendor->business_name . ' telah aktif kembali.')
            ->action('Kelola Armada', url('/vendor'))
            ->line('Terima kasih atas kesabaran Anda.');
    }

    public function toArray($notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'message'   => 'Akun vendor aktif kembali',
        ];
    }
}
