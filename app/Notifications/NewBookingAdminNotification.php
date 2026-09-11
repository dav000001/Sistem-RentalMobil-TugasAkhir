<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke admin saat booking baru dibuat oleh customer.
 * Tidak pakai ShouldQueue agar langsung masuk database (QUEUE_CONNECTION=sync).
 */
class NewBookingAdminNotification extends Notification
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
            'title'        => '🔔 Booking Baru',
            'body'         => 'Customer ' . ($this->booking->customer?->full_name ?? '—')
                            . ' memesan ' . $this->booking->car->brand . ' ' . $this->booking->car->model
                            . ' | Kode: ' . $this->booking->code
                            . ' | Total: Rp ' . number_format($this->booking->total, 0, ',', '.'),
            'icon'         => 'heroicon-o-calendar-days',
            'color'        => 'warning',
            'url'          => url('/admin/bookings/' . $this->booking->id . '/edit'),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
        ];
    }
}
