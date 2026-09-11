<?php

namespace App\Notifications;

use App\Models\CarChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CarChangeApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public CarChangeRequest $request) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $newCar   = $this->request->newCar;
        $diff     = $this->request->price_difference ?? 0;
        $message  = 'Permintaan ganti mobil Anda pada booking #' . $this->request->booking->code
                  . ' telah DISETUJUI oleh vendor. '
                  . 'Mobil baru: ' . ($newCar ? $newCar->brand . ' ' . $newCar->model : '—') . '.';

        if ($diff > 0) {
            $message .= ' Selisih harga yang perlu dibayar: Rp ' . number_format($diff, 0, ',', '.');
        }

        return [
            'title'      => '✅ Ganti Mobil Disetujui',
            'message'    => $message,
            'booking_id' => $this->request->booking_id,
            'code'       => $this->request->booking->code,
            'type'       => 'car_change_approved',
            'url'        => '/my-bookings/' . $this->request->booking->code,
        ];
    }
}
