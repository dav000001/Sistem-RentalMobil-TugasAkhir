<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payout $payout) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('💰 Payout Telah Dikonfirmasi')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Payout Anda telah dikonfirmasi dan sedang diproses.')
            ->line('Jumlah: Rp ' . number_format($this->payout->amount, 0, ',', '.'))
            ->line('Periode: ' . $this->payout->period_start->format('d M Y') . ' – ' . $this->payout->period_end->format('d M Y'))
            ->line('Dana akan ditransfer ke rekening Anda dalam 1–3 hari kerja.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'    => 'filament',
            'title'     => '💰 Payout Dikonfirmasi',
            'body'      => 'Rp ' . number_format($this->payout->amount, 0, ',', '.')
                         . ' | Periode: ' . $this->payout->period_start->format('d M Y')
                         . ' – ' . $this->payout->period_end->format('d M Y'),
            'icon'      => 'heroicon-o-banknotes',
            'color'     => 'success',
            'url'       => url('/vendor/payouts'),
            'payout_id' => $this->payout->id,
            'amount'    => $this->payout->amount,
        ];
    }
}
