<?php

namespace App\Notifications;

use App\Models\LateReturnReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateReturnReportedToCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LateReturnReport $report) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->report->booking;
        $hours   = $this->report->estimated_late_hours;

        return (new MailMessage)
            ->subject("🔔 Info Keterlambatan — Booking {$booking->code}")
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line("Vendor telah mencatat bahwa booking **{$booking->code}** akan mengalami keterlambatan pengembalian.")
            ->line("**Perkiraan keterlambatan sopir:** {$hours} jam")
            ->line('Denda akan dihitung berdasarkan waktu aktual pengembalian.')
            ->action('Lihat Detail Booking', url('/bookings/' . $booking->code))
            ->salutation('Tim Rental Mobil');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'late_return_reported_customer',
            'booking_id'      => $this->report->booking_id,
            'booking_code'    => $this->report->booking?->code,
            'estimated_hours' => $this->report->estimated_late_hours,
            'message'         => 'Vendor melaporkan keterlambatan sopir pada booking '
                . $this->report->booking?->code
                . ' ~' . $this->report->estimated_late_hours . ' jam.',
        ];
    }
}
