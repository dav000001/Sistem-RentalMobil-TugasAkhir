<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke vendor saat booking baru dibuat oleh customer.
 * Tidak pakai ShouldQueue agar langsung masuk database (QUEUE_CONNECTION=sync).
 */
class NewBookingVendorNotification extends Notification
{
    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'       => 'filament',
            'title'        => '🚗 Pesanan Baru Masuk',
            'body'         => 'Kode: ' . $this->booking->code
                            . ' | Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model
                            . ' | Total: Rp ' . number_format($this->booking->total, 0, ',', '.')
                            . ' | Menunggu pembayaran customer.',
            'icon'         => 'heroicon-o-calendar-days',
            'color'        => 'warning',
            'url'          => url('/vendor/bookings/' . $this->booking->id . '/edit'),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
        ];
    }
}
