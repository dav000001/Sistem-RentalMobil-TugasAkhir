<?php

namespace App\Notifications;

use App\Models\CompensationCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompensationPaidNotification extends Notification
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
        $amount   = 'Rp ' . number_format($this->charge->amount, 0, ',', '.');
        $customer = $this->charge->customer?->full_name ?? '-';

        return (new MailMessage)
            ->subject('Kompensasi Sudah Dibayar - Booking ' . ($booking?->code ?? '-'))
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Kabar baik! Customer **' . $customer . '** telah membayar kompensasi.')
            ->line('**Booking:** ' . ($booking?->code ?? '-') . ' - ' . ($booking?->car?->brand . ' ' . $booking?->car?->model))
            ->line('**Jumlah:** ' . $amount)
            ->line('**Alasan:** ' . $this->charge->reason)
            ->line('Dana kompensasi akan diteruskan ke Anda sesuai kebijakan platform.')
            ->action('Lihat Detail Pemesanan', url('/vendor/bookings/' . $booking?->id))
            ->line('Terima kasih atas kesabaran Anda.');
    }

    public function toArray(object $notifiable): array
    {
        $booking = $this->charge->booking;
        $amount  = 'Rp ' . number_format($this->charge->amount, 0, ',', '.');

        return [
            'format'     => 'filament',
            'title'      => '💰 Kompensasi Sudah Dibayar - ' . $amount,
            'body'       => 'Customer ' . ($this->charge->customer?->full_name ?? '-')
                          . ' telah membayar kompensasi untuk booking '
                          . ($booking?->code ?? '-'),
            'icon'       => 'heroicon-o-check-circle',
            'color'      => 'success',
            'url'        => '/vendor/bookings/' . $booking?->id,
        ];
    }
}
