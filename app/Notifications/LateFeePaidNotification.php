<?php

namespace App\Notifications;

use App\Models\LateFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateFeePaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LateFeeCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->charge->booking;
        $amount  = 'Rp ' . number_format($this->charge->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject('✅ Denda Terkonfirmasi Lunas — ' . $booking->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Pembayaran denda keterlambatan untuk booking **' . $booking->code . '** telah dikonfirmasi.')
            ->line('**Jumlah Denda:** ' . $amount)
            ->line('Terima kasih sudah menyelesaikan tagihan denda. Sampai jumpa di booking berikutnya!')
            ->action('Lihat Detail Booking', url('/bookings/' . $booking->code))
            ->salutation('Salam, Tim Rental Mobil');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'late_fee_paid',
            'booking_id'   => $this->charge->booking_id,
            'booking_code' => $this->charge->booking?->code,
            'charge_id'    => $this->charge->id,
            'amount'       => $this->charge->amount,
            'message'      => 'Pembayaran denda keterlambatan booking '
                . $this->charge->booking?->code
                . ' sebesar Rp ' . number_format($this->charge->amount, 0, ',', '.')
                . ' telah dikonfirmasi lunas.',
        ];
    }
}
