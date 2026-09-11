<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke admin saat customer upload bukti transfer.
 * Admin perlu mengkonfirmasi pembayaran.
 */
class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'       => 'filament',
            'title'        => '💳 Bukti Transfer Masuk',
            'body'         => 'Customer ' . ($this->booking->customer?->full_name ?? '—')
                            . ' upload bukti transfer untuk booking ' . $this->booking->code
                            . ' | Mobil: ' . $this->booking->car->brand . ' ' . $this->booking->car->model
                            . ' | Total: Rp ' . number_format($this->booking->total, 0, ',', '.'),
            'icon'         => 'heroicon-o-credit-card',
            'color'        => 'success',
            'url'          => url('/admin/bookings/' . $this->booking->id . '/edit'),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
            'amount'       => $this->booking->total,
        ];
    }
}
