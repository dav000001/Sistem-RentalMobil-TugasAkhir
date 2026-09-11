<?php

namespace App\Notifications;

use App\Models\LateFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateFeeWaivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LateFeeCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $charge  = $this->charge;
        $booking = $charge->booking;

        return (new MailMessage)
            ->subject('🎁 Denda Dibebaskan — ' . $booking->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Kabar baik! Admin telah membebaskan denda keterlambatan untuk booking **' . $booking->code . '**.')
            ->line('**Alasan:** ' . ($charge->waive_reason ?? '—'))
            ->line('Anda tidak perlu melakukan pembayaran denda.')
            ->action('Lihat Detail Booking', url('/bookings/' . $booking->code))
            ->salutation('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'late_fee_waived',
            'booking_id'   => $this->charge->booking_id,
            'booking_code' => $this->charge->booking?->code,
            'charge_id'    => $this->charge->id,
            'message'      => 'Denda keterlambatan booking '
                . $this->charge->booking?->code
                . ' telah dibebaskan oleh admin.',
        ];
    }
}
