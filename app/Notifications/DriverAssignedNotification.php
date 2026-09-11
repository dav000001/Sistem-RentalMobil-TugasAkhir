<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly Driver  $driver,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🧑‍✈️ Sopir Ditugaskan — Booking ' . $this->booking->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Sopir untuk pesanan **' . $this->booking->code . '** telah ditugaskan.')
            ->line('**Nama Sopir:** ' . $this->driver->name)
            ->line('**No. Telepon:** ' . $this->driver->phone)
            ->line('Anda dapat menghubungi sopir langsung melalui nomor di atas.')
            ->action('Lihat Detail Booking', url('/bookings/' . $this->booking->code))
            ->salutation('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'driver_assigned',
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
            'driver_name'  => $this->driver->name,
            'driver_phone' => $this->driver->phone,
            'message'      => 'Sopir ' . $this->driver->name
                . ' telah ditugaskan untuk booking ' . $this->booking->code
                . '. Hubungi: ' . $this->driver->phone,
        ];
    }
}
