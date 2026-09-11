<?php

namespace App\Notifications;

use App\Models\CarChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CarChangeRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public CarChangeRequest $request) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $refundAmount = $this->request->vendor_penalty_amount
            ? number_format((float)$this->request->original_total * 0.5, 0, ',', '.')
            : null;

        return [
            'title'      => '❌ Ganti Mobil Ditolak — Booking Dibatalkan',
            'message'    => 'Vendor tidak memiliki mobil pengganti yang sesuai untuk booking #'
                          . $this->request->booking->code . '. '
                          . 'Booking telah dibatalkan. Anda akan mendapatkan refund 50% dari total pembayaran'
                          . ($refundAmount ? ' (Rp ' . $refundAmount . ')' : '')
                          . ' yang akan diproses oleh admin. '
                          . ($this->request->vendor_notes
                              ? 'Catatan vendor: ' . $this->request->vendor_notes
                              : ''),
            'booking_id' => $this->request->booking_id,
            'code'       => $this->request->booking->code,
            'type'       => 'car_change_rejected',
            'url'        => '/my-bookings/' . $this->request->booking->code,
        ];
    }
}
