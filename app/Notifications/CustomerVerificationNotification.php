<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $status,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->status === 'verified') {
            return (new MailMessage)
                ->subject('Verifikasi Identitas Disetujui ✅')
                ->greeting('Halo ' . $notifiable->name . '!')
                ->line('Selamat! Verifikasi identitas Anda telah **disetujui**.')
                ->line('Anda sekarang dapat melakukan pemesanan mobil.')
                ->action('Cari Mobil Sekarang', url('/search'))
                ->line('Terima kasih telah menggunakan layanan kami!');
        }

        return (new MailMessage)
            ->subject('Verifikasi Identitas Ditolak ❌')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Maaf, verifikasi identitas Anda **ditolak** oleh admin.')
            ->line('**Alasan:** ' . ($this->reason ?? 'Dokumen tidak memenuhi syarat.'))
            ->line('Silakan upload ulang dokumen yang lebih jelas dan sesuai.')
            ->action('Upload Ulang Dokumen', url('/profile'))
            ->line('Jika ada pertanyaan, hubungi kami melalui halaman Hubungi Kami.');
    }

    public function toArray(object $notifiable): array
    {
        if ($this->status === 'verified') {
            return [
                'title' => '✅ Verifikasi Disetujui',
                'body'  => 'Identitas Anda telah diverifikasi. Anda dapat melakukan pemesanan.',
                'icon'  => 'heroicon-o-check-circle',
                'color' => 'success',
                'url'   => url('/search'),
            ];
        }

        return [
            'title' => '❌ Verifikasi Ditolak',
            'body'  => 'Verifikasi ditolak. Alasan: ' . ($this->reason ?? 'Dokumen tidak memenuhi syarat.') . ' Silakan upload ulang.',
            'icon'  => 'heroicon-o-x-circle',
            'color' => 'danger',
            'url'   => url('/profile'),
        ];
    }
}
