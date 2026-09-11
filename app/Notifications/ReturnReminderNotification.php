<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReturnReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $endAtFormatted = $this->booking->end_at?->format('d M Y, H:i');
        $carTitle       = ($this->booking->car?->brand ?? '') . ' ' . ($this->booking->car?->model ?? '');

        return (new MailMessage)
            ->subject('⏰ Pengingat: Waktu Sewa Berakhir 2 Jam Lagi — ' . $this->booking->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line("Masa sewa kendaraan **{$carTitle}** (Booking **{$this->booking->code}**) akan berakhir pada **{$endAtFormatted}** (sekitar 2 jam lagi).")
            ->line('Mohon bersiap mengembalikan kendaraan tepat waktu ke lokasi vendor untuk menghindari denda keterlambatan.')
            ->line('Jika Anda mengetahui akan keterlambatan, Anda dapat melaporkannya melalui halaman detail booking.')
            ->action('Lihat Detail Booking', url('/bookings/' . $this->booking->code))
            ->salutation('Terima kasih, Tim Rental Mobil');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'return_reminder',
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
            'end_at'       => $this->booking->end_at?->toIso8601String(),
            'message'      => 'Pengingat: Waktu sewa booking ' . $this->booking->code . ' akan berakhir sekitar 2 jam lagi (' . $this->booking->end_at?->format('H:i') . ').',
        ];
    }
}
