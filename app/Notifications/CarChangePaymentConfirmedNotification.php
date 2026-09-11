<?php

namespace App\Notifications;

use App\Models\CarChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CarChangePaymentConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public CarChangeRequest $request) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $newCar = $this->request->newCar;

        return [
            'title'      => '💰 Pembayaran Selisih Dikonfirmasi',
            'message'    => 'Pembayaran selisih harga untuk booking #'
                          . $this->request->booking->code
                          . ' telah dikonfirmasi oleh admin. '
                          . 'Mobil Anda telah resmi diganti ke '
                          . ($newCar ? $newCar->brand . ' ' . $newCar->model : '—') . '.',
            'booking_id' => $this->request->booking_id,
            'code'       => $this->request->booking->code,
            'type'       => 'car_change_payment_confirmed',
            'url'        => '/my-bookings/' . $this->request->booking->code,
        ];
    }
}
