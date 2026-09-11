<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payout $payout, public string $reason = '') {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('⚠️ Payout Gagal Diproses')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Maaf, payout Anda gagal diproses.')
            ->line('Jumlah: **Rp ' . number_format($this->payout->amount, 0, ',', '.') . '**');

        if ($this->reason) {
            $mail->line('Alasan: ' . $this->reason);
        }

        return $mail
            ->line('Tim kami akan menghubungi Anda atau silakan pastikan data rekening bank Anda sudah benar.')
            ->action('Perbarui Data Rekening', url('/profile'))
            ->line('Jika ada pertanyaan, hubungi admin melalui halaman support.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'    => 'filament',
            'title'     => '⚠️ Payout Gagal',
            'body'      => 'Rp ' . number_format($this->payout->amount, 0, ',', '.')
                         . ' gagal ditransfer.'
                         . ($this->reason ? ' Alasan: ' . $this->reason : ''),
            'icon'      => 'heroicon-o-x-circle',
            'color'     => 'danger',
            'url'       => url('/profile'),
            'payout_id' => $this->payout->id,
            'amount'    => $this->payout->amount,
        ];
    }
}
