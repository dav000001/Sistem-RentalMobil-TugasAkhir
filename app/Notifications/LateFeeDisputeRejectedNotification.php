<?php

namespace App\Notifications;

use App\Models\LateFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateFeeDisputeRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LateFeeCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->charge->booking;

        return (new MailMessage)
            ->subject('❌ Penolakan Keberatan Denda — ' . $booking?->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line("Keberatan Denda yang Anda ajukan untuk booking **{$booking?->code}** telah ditinjau oleh Admin.")
            ->line('**Keputusan Admin:** Keberatan Ditolak (Denda Tetap Berlaku).')
            ->line('**Alasan Penolakan dari Admin:** ' . ($this->charge->dispute_rejection_reason ?? '—'))
            ->line('**Jumlah Tagihan Denda:** Rp ' . number_format($this->charge->amount, 0, ',', '.'))
            ->line('Silakan lakukan pembayaran denda melalui rekening yang tertera di halaman detail booking.')
            ->action('Lihat Detail Booking & Bayar Denda', url('/bookings/' . $booking?->code))
            ->salutation('Terima kasih, Tim Rental Mobil');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'late_fee_dispute_rejected',
            'booking_id'   => $this->charge->booking_id,
            'booking_code' => $this->charge->booking?->code,
            'charge_id'    => $this->charge->id,
            'amount'       => $this->charge->amount,
            'reason'       => $this->charge->dispute_rejection_reason,
            'message'      => 'Keberatan denda untuk booking ' . $this->charge->booking?->code . ' ditolak oleh Admin: ' . $this->charge->dispute_rejection_reason,
        ];
    }
}
