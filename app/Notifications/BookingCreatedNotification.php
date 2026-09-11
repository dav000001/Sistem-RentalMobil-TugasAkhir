<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke vendor saat admin mengkonfirmasi pembayaran (awaiting_payment → awaiting_vendor).
 * Vendor perlu segera mengkonfirmasi pesanan.
 */
class BookingCreatedNotification extends Notification
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
            ->subject('Pesanan Siap Dikonfirmasi - ' . $this->booking->code)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Pembayaran customer sudah dikonfirmasi. Pesanan menunggu konfirmasi Anda.')
            ->line('Kode Pesanan: ' . $this->booking->code)
            ->line('Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model)
            ->line('Total: Rp ' . number_format($this->booking->total, 0, ',', '.'))
            ->action('Konfirmasi Sekarang', url('/vendor/bookings/' . $this->booking->id . '/edit'))
            ->line('Mohon segera konfirmasi pesanan ini.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'       => 'filament',
            'title'        => '🔔 Pesanan Siap Dikonfirmasi',
            'body'         => 'Kode: ' . $this->booking->code
                            . ' | Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model
                            . ' | Total: Rp ' . number_format($this->booking->total, 0, ',', '.'),
            'icon'         => 'heroicon-o-calendar',
            'color'        => 'warning',
            'url'          => url('/vendor/bookings/' . $this->booking->id . '/edit'),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
        ];
    }
}
