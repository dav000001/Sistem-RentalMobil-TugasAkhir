<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Dispute $dispute,
        public string  $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking  = $this->dispute->booking;
        $car      = $booking ? $booking->car : null;
        $carLabel = $car ? ($car->brand . ' ' . $car->model) : '-';
        $code     = $booking ? ($booking->code ?? '-') : '-';

        $statusLabel = 'Update';
        if ($this->newStatus === 'in_review') $statusLabel = 'Sedang Ditinjau';
        if ($this->newStatus === 'resolved')  $statusLabel = 'Diselesaikan';
        if ($this->newStatus === 'rejected')  $statusLabel = 'Ditolak';

        // Tentukan apakah penerima adalah vendor
        $vendorUserId = $booking ? optional($booking->vendor)->user_id : null;
        $isVendor = $vendorUserId && $vendorUserId === $notifiable->id;

        $mail = (new MailMessage)
            ->subject('Dispute #' . $this->dispute->id . ' - ' . $statusLabel)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Status dispute untuk booking ' . $code . ' (' . $carLabel . ') telah diperbarui menjadi ' . $statusLabel . '.');

        if ($this->newStatus === 'in_review') {
            $mail->line('Tim admin kami sedang meninjau dispute ini. Semua pihak akan dinotifikasi saat ada keputusan.');
        }

        if (in_array($this->newStatus, ['resolved', 'rejected']) && $this->dispute->resolution) {
            $mail->line('Keputusan Admin:')
                 ->line($this->dispute->resolution);
        }

        $actionUrl = $isVendor
            ? url('/vendor/bookings/' . optional($booking)->id)
            : route('bookings.show', $booking);

        return $mail
            ->action('Lihat Detail Booking', $actionUrl)
            ->line('Terima kasih telah menggunakan layanan kami.');
    }

    public function toArray(object $notifiable): array
    {
        $booking = $this->dispute->booking;

        // Tentukan apakah penerima adalah vendor
        $vendorUserId = $booking ? optional($booking->vendor)->user_id : null;
        $isVendor = $vendorUserId && $vendorUserId === $notifiable->id;

        $icon  = 'heroicon-o-chat-bubble-left-right';
        $color = 'gray';
        $label = 'Update Dispute';

        if ($this->newStatus === 'in_review') {
            $icon  = 'heroicon-o-eye';
            $color = 'info';
            $label = 'Dispute Sedang Ditinjau';
        } elseif ($this->newStatus === 'resolved') {
            $icon  = 'heroicon-o-check-circle';
            $color = 'success';
            $label = 'Dispute Diselesaikan';
        } elseif ($this->newStatus === 'rejected') {
            $icon  = 'heroicon-o-x-circle';
            $color = 'danger';
            $label = 'Dispute Ditolak';
        }

        $bookingCode = $booking ? ($booking->code ?? '-') : '-';
        $body = 'Dispute untuk booking ' . $bookingCode;

        if (in_array($this->newStatus, ['resolved', 'rejected']) && $this->dispute->resolution) {
            $body .= ': ' . \Illuminate\Support\Str::limit($this->dispute->resolution, 80);
        } else {
            $body .= ' sedang ditinjau oleh admin.';
        }

        $url = $isVendor
            ? '/vendor/bookings/' . optional($booking)->id
            : route('bookings.show', $booking);

        return [
            'format'     => 'filament',
            'title'      => $label,
            'body'       => $body,
            'icon'       => $icon,
            'color'      => $color,
            'url'        => $url,
            'dispute_id' => $this->dispute->id,
            'booking_id' => optional($booking)->id,
        ];
    }
}
