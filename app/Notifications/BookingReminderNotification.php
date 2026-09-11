<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification
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
            ->subject('Pengingat Pesanan - ' . $this->booking->code)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Pesanan Anda akan dimulai besok.')
            ->line('Kode Pesanan: ' . $this->booking->code)
            ->line('Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model)
            ->line('Tanggal Mulai: ' . $this->booking->start_at->format('d M Y H:i'))
            ->line('Lokasi: ' . $this->booking->pickup_location)
            ->action('Lihat Detail', route('bookings.show', $this->booking));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'       => 'filament',
            'title'        => '⏰ Pengingat Pesanan',
            'body'         => 'Pesanan ' . $this->booking->code
                            . ' untuk ' . $this->booking->car->brand . ' ' . $this->booking->car->model
                            . ' akan dimulai besok, ' . $this->booking->start_at->format('d M Y H:i'),
            'icon'         => 'heroicon-o-bell',
            'color'        => 'info',
            'url'          => route('bookings.show', $this->booking),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
        ];
    }
}
