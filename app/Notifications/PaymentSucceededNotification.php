<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject('Pembayaran Dikonfirmasi - ' . $booking->code)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Pembayaran Anda telah dikonfirmasi oleh admin.')
            ->line('Kode Pesanan: ' . $booking->code)
            ->line('Mobil: ' . $booking->car->brand . ' ' . $booking->car->model)
            ->line('Total: Rp ' . number_format($this->payment->amount, 0, ',', '.'))
            ->line('Tanggal Bayar: ' . ($this->payment->paid_at?->format('d M Y H:i') ?? now()->format('d M Y H:i')))
            ->action('Lihat Detail Pesanan', route('bookings.show', $booking))
            ->line('Vendor akan segera mengkonfirmasi pesanan Anda.');
    }

    public function toArray(object $notifiable): array
    {
        $booking = $this->payment->booking;

        return [
            'format'       => 'filament',
            'title'        => '💳 Pembayaran Dikonfirmasi',
            'body'         => 'Pembayaran untuk booking ' . $booking->code
                            . ' sebesar Rp ' . number_format($this->payment->amount, 0, ',', '.')
                            . ' telah dikonfirmasi. Menunggu konfirmasi vendor.',
            'icon'         => 'heroicon-o-credit-card',
            'color'        => 'success',
            'url'          => route('bookings.show', $booking),
            'booking_id'   => $booking->id,
            'booking_code' => $booking->code,
            'amount'       => $this->payment->amount,
        ];
    }
}
