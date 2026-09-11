<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeOpenedNotification extends Notification
{
    use Queueable;

    public function __construct(public Dispute $dispute) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking   = $this->dispute->booking;
        $openedBy  = $this->dispute->openedBy;
        $car       = $booking?->car;
        $carLabel  = $car ? $car->brand . ' ' . $car->model : '—';
        $roleLabel = ($this->dispute->opened_by_role ?? 'customer') === 'vendor' ? 'Vendor' : 'Customer';

        return (new MailMessage)
            ->subject('⚠️ Sengketa Baru (' . $roleLabel . ') — Booking ' . ($booking?->code ?? '—'))
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Sebuah sengketa baru telah diajukan oleh **' . $roleLabel . ' ' . ($openedBy?->name ?? '—') . '**.')
            ->line('**Booking:** ' . ($booking?->code ?? '—') . ' — ' . $carLabel)
            ->line('**Alasan:** ' . \Illuminate\Support\Str::limit($this->dispute->reason, 120))
            ->action('Tinjau Sengketa', url('/admin/sengketa/' . $this->dispute->id . '/edit'))
            ->line('Harap ditangani dalam 3 hari kerja.');
    }

    public function toArray(object $notifiable): array
    {
        $booking   = $this->dispute->booking;
        $roleLabel = ($this->dispute->opened_by_role ?? 'customer') === 'vendor' ? '🏪 Vendor' : '👤 Customer';

        return [
            'format'     => 'filament',
            'title'      => '⚠️ Sengketa Baru — Dibuka oleh ' . $roleLabel,
            'body'       => 'Booking ' . ($booking?->code ?? '—') . ': '
                          . \Illuminate\Support\Str::limit($this->dispute->reason, 80),
            'icon'       => 'heroicon-o-exclamation-triangle',
            'color'      => 'danger',
            'url'        => '/admin/sengketa/' . $this->dispute->id . '/edit',
            'dispute_id' => $this->dispute->id,
            'booking_id' => $booking?->id,
        ];
    }
}
