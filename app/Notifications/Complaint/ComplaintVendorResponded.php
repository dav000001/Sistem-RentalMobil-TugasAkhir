<?php

namespace App\Notifications\Complaint;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintVendorResponded extends Notification
{
    use Queueable;

    public function __construct(public readonly Complaint $complaint) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendorName = $this->complaint->vendor?->business_name ?? 'Vendor';
        $booking    = $this->complaint->booking;

        return (new MailMessage)
            ->subject('💬 Vendor Sudah Merespons — ' . $this->complaint->reference)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Vendor **' . $vendorName . '** telah memberikan respons pada komplain berikut:')
            ->line('**Referensi:** ' . $this->complaint->reference)
            ->line('**Booking:** ' . ($booking?->code ?? '-') . ' — ' . ($booking?->car?->brand . ' ' . $booking?->car?->model))
            ->line('Silakan tinjau respons vendor dan ambil keputusan final.')
            ->action('Tinjau Sekarang', url('/admin/complaints/' . $this->complaint->id))
            ->line('Komplain ini menunggu keputusan Anda.');
    }

    public function toArray(object $notifiable): array
    {
        $vendorName = $this->complaint->vendor?->business_name ?? 'Vendor';

        return [
            'format'       => 'filament',
            'title'        => '💬 Vendor Sudah Merespons — ' . $this->complaint->reference,
            'body'         => $vendorName . ' telah memberikan respons. Tinjau dan ambil keputusan.',
            'icon'         => 'heroicon-o-chat-bubble-left-right',
            'color'        => 'info',
            'url'          => '/admin/complaints/' . $this->complaint->id,
            'complaint_id' => $this->complaint->id,
            'reference'    => $this->complaint->reference,
        ];
    }
}
