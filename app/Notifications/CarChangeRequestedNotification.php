<?php

namespace App\Notifications;

use App\Models\CarChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class CarChangeRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(public CarChangeRequest $request) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title'      => '🔄 Permintaan Ganti Mobil',
            'message'    => 'Customer ' . $this->request->customer->full_name
                          . ' meminta pergantian mobil pada booking #'
                          . $this->request->booking->code
                          . ' (penumpang: ' . $this->request->passenger_count . ' orang).',
            'booking_id' => $this->request->booking_id,
            'code'       => $this->request->booking->code,
            'type'       => 'car_change_request',
            'url'        => '/vendor/bookings/' . $this->request->booking_id . '/edit',
        ];
    }
}
