<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeOpenedVendorNotification extends Notification
{
    use Queueable;

    public function __construct(public Dispute $dispute) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking  = $this->dispute->booking;
        $openedBy = $this->dispute->openedBy;
        $car      = $booking?->car;
        $carLabel = $car ? $car->brand . ' ' . $car->model . ' (' . $car->plate_number . ')' : '—';

        return (new MailMessage)
            ->subject('⚠️ Sengketa Diajukan Customer — Booking ' . ($booking?->code ?? '—'))
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Customer **' . ($openedBy?->name ?? '—') . '** telah mengajukan sengketa untuk pemesanan Anda.')
            ->line('**Booking:** ' . ($booking?->code ?? '—') . ' — ' . $carLabel)
            ->line('**Alasan:** ' . \Illuminate\Support\Str::limit($this->dispute->reason, 200))
            ->line('Tim admin kami akan meninjau sengketa ini dalam 1×3 hari kerja. Anda akan mendapat notifikasi saat ada keputusan.')
            ->line('Jika Anda memiliki bukti atau keterangan tambahan, silakan hubungi admin melalui menu **Bantuan & Support**.')
            ->action('Lihat Detail Pemesanan', url('/vendor/bookings/' . $booking?->id))
            ->line('Terima kasih atas kerjasamanya.');
    }

    public function toArray(object $notifiable): array
    {
        $booking  = $this->dispute->booking;
        $openedBy = $this->dispute->openedBy;

        return [
            'format'     => 'filament',
            'title'      => '⚠️ Sengketa Diajukan — ' . ($booking?->code ?? '—'),
            'body'       => 'Customer ' . ($openedBy?->name ?? '—') . ' mengajukan sengketa: '
                          . \Illuminate\Support\Str::limit($this->dispute->reason, 80),
            'icon'       => 'heroicon-o-exclamation-triangle',
            'color'      => 'warning',
            'url'        => '/vendor/bookings/' . $booking?->id,
            'dispute_id' => $this->dispute->id,
            'booking_id' => $booking?->id,
        ];
    }
}
