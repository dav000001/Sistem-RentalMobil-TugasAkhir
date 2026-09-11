<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateReturnCustomerNotification extends Notification implements ShouldQueue
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
            ->subject('⚠️ Keterlambatan Pengembalian Mobil — ' . $booking->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Kami mencatat bahwa mobil dari booking **' . $booking->code . '** dikembalikan terlambat.')
            ->line('**Durasi terlambat:** ' . $booking->late_duration_hours . ' jam')
            ->line('**Denda keterlambatan:** ' . $fee)
            ->line('Denda akan ditambahkan ke tagihan Anda. Silakan hubungi vendor jika ada pertanyaan.')
            ->action('Lihat Detail Booking', url('/bookings/' . $booking->code))
            ->salutation('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'late_return_customer',
            'booking_id'  => $this->booking->id,
            'booking_code' => $this->booking->code,
            'late_hours'  => $this->booking->late_duration_hours,
            'late_fee'    => $this->booking->late_fee,
            'message'     => 'Pengembalian mobil booking ' . $this->booking->code
                . ' terlambat ' . $this->booking->late_duration_hours . ' jam.'
                . ' Denda: Rp ' . number_format($this->booking->late_fee, 0, ',', '.'),
        ];
    }
}
