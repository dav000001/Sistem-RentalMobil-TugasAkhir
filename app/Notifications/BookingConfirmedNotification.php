<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification
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
            ->subject('Pesanan Dikonfirmasi - ' . $this->booking->code)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Pesanan Anda telah dikonfirmasi oleh vendor.')
            ->line('Kode Pesanan: ' . $this->booking->code)
            ->line('Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model)
            ->line('Tanggal Mulai: ' . $this->booking->start_at->format('d M Y H:i'))
            ->line('Lokasi Penjemputan: ' . $this->booking->pickup_location)
            ->action('Lihat Detail', route('bookings.show', $this->booking))
            ->line('Terima kasih telah menggunakan layanan kami!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'       => 'filament',
            'title'        => '✅ Pesanan Dikonfirmasi',
            'body'         => 'Kode: ' . $this->booking->code
                            . ' | Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model
                            . ' | Mulai: ' . $this->booking->start_at->format('d M Y'),
            'icon'         => 'heroicon-o-check-circle',
            'color'        => 'success',
            'url'          => route('bookings.show', $this->booking),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
        ];
    }
}
