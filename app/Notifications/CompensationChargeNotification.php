<?php

namespace App\Notifications;

use App\Models\CompensationCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompensationChargeNotification extends Notification
{
    use Queueable;

    public function __construct(public CompensationCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking  = $this->charge->booking;
        $car      = $booking?->car;
        $carLabel = $car ? $car->brand . ' ' . $car->model : '—';
        $amount   = 'Rp ' . number_format($this->charge->amount, 0, ',', '.');
        $due      = $this->charge->due_date?->format('d M Y') ?? '—';

        return (new MailMessage)
            ->subject('⚠️ Tagihan Kompensasi — Booking ' . ($booking?->code ?? '—'))
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Anda memiliki tagihan kompensasi terkait sengketa pada booking berikut:')
            ->line('**Booking:** ' . ($booking?->code ?? '—') . ' — ' . $carLabel)
            ->line('**Alasan:** ' . $this->charge->reason)
            ->line('**Jumlah:** ' . $amount)
            ->line('**Batas Pembayaran:** ' . $due)
            ->action('Lihat Detail Pesanan', url('/bookings/' . ($booking?->code ?? '')))
            ->line('Silakan upload bukti pembayaran melalui halaman detail pesanan Anda.')
            ->line('Jika ada pertanyaan, hubungi admin melalui halaman Hubungi Kami.');
    }

    public function toArray(object $notifiable): array
    {
        $booking = $this->charge->booking;
        $amount  = 'Rp ' . number_format($this->charge->amount, 0, ',', '.');

        return [
            'format'     => 'notification',
            'title'      => '⚠️ Tagihan Kompensasi - ' . $amount,
            'body'       => 'Booking ' . ($booking?->code ?? '-') . ': ' . $this->charge->reason . '. Batas bayar: ' . ($this->charge->due_date?->format('d M Y') ?? '-'),
            'icon'       => 'heroicon-o-exclamation-triangle',
            'color'      => 'danger',
            'url'        => '/bookings/' . ($booking?->code ?? ''),
            'type'       => 'compensation_charge',
            'amount'     => $this->charge->amount,
            'booking_id' => $booking?->id,
            'booking_code' => $booking?->code,
        ];
    }
}
