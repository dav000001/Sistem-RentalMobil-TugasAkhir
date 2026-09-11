<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pesanan Dibatalkan - ' . $this->booking->code)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Pesanan telah dibatalkan.')
            ->line('Kode Pesanan: ' . $this->booking->code)
            ->line('Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model)
            ->action('Lihat Detail', route('bookings.show', $this->booking))
            ->line('Jika ada pertanyaan, silakan hubungi kami.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'       => 'filament',
            'title'        => '❌ Pesanan Dibatalkan',
            'body'         => 'Kode: ' . $this->booking->code
                            . ' | Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model,
            'icon'         => 'heroicon-o-x-circle',
            'color'        => 'danger',
            'url'          => route('bookings.show', $this->booking),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
        ];
    }
}
