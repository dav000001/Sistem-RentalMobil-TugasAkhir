<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NeedsRevisionNotification extends Notification
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
            ->subject('Dokumen Vendor Perlu Diperbaiki')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Beberapa dokumen vendor ' . $this->vendor->business_name . ' perlu diperbaiki.')
            ->line('Catatan: ' . $this->reason)
            ->action('Upload Ulang Dokumen', url('/vendor/onboarding'))
            ->line('Silakan login dan upload ulang dokumen yang diminta.');
    }

    public function toArray($notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'message'   => 'Dokumen perlu revisi: ' . $this->reason,
        ];
    }
}
