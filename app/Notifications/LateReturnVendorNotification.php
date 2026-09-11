<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateReturnVendorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;
        $fee     = 'Rp ' . number_format($booking->late_fee, 0, ',', '.');

        return (new MailMessage)
            ->subject('⚠️ Keterlambatan Dikembalikan — Booking ' . $booking->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Booking **' . $booking->code . '** dari customer **' . $booking->customer?->full_name . '** dikembalikan terlambat.')
            ->line('**Durasi terlambat:** ' . $booking->late_duration_hours . ' jam')
            ->line('**Denda:** ' . $fee)
            ->action('Lihat Detail Booking', url('/vendor/bookings'))
            ->salutation('Tim Rental Mobil');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'late_return_vendor',
            'booking_id'    => $this->booking->id,
            'booking_code'  => $this->booking->code,
            'customer_name' => $this->booking->customer?->full_name,
            'late_hours'    => $this->booking->late_duration_hours,
            'late_fee'      => $this->booking->late_fee,
            'message'       => 'Customer ' . $this->booking->customer?->full_name
                . ' terlambat mengembalikan mobil (' . $this->booking->late_duration_hours . ' jam).'
                . ' Denda: Rp ' . number_format($this->booking->late_fee, 0, ',', '.'),
        ];
    }
}
