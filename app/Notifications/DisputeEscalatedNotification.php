<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeEscalatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Dispute $dispute,
        public int     $days
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking  = $this->dispute->booking;
        $carLabel = $booking?->car ? $booking->car->brand . ' ' . $booking->car->model : '—';

        return (new MailMessage)
            ->subject('🚨 [ESCALATED] Dispute #' . $this->dispute->id . ' Belum Ditangani ' . $this->days . ' Hari')
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('⚠️ **Dispute berikut belum ditangani selama ' . $this->days . ' hari** dan telah otomatis di-escalate.')
            ->line('**Booking:** ' . ($booking?->code ?? '—') . ' — ' . $carLabel)
            ->line('**Dibuka oleh:** ' . ($this->dispute->openedBy?->name ?? '—'))
            ->line('**Alasan:** ' . \Illuminate\Support\Str::limit($this->dispute->reason, 150))
            ->line('Status dispute telah diubah ke **Sedang Ditinjau**. Harap segera ditangani.')
            ->action('Tangani Sekarang', url('/admin/sengketa/' . $this->dispute->id . '/edit'))
            ->line('Keterlambatan penanganan dapat mempengaruhi kepuasan pengguna platform.');
    }

    public function toArray(object $notifiable): array
    {
        $booking = $this->dispute->booking;

        return [
            'format'     => 'filament',
            'title'      => '🚨 Dispute Ter-escalate — ' . $this->days . ' Hari Tidak Ditangani',
            'body'       => 'Booking ' . ($booking?->code ?? '—') . ': '
                          . \Illuminate\Support\Str::limit($this->dispute->reason, 80),
            'icon'       => 'heroicon-o-bell-alert',
            'color'      => 'danger',
            'url'        => '/admin/sengketa/' . $this->dispute->id . '/edit',
            'dispute_id' => $this->dispute->id,
            'booking_id' => $booking?->id,
        ];
    }
}
