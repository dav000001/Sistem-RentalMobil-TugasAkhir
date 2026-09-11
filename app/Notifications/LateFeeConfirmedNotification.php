<?php

namespace App\Notifications;

use App\Models\LateFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateFeeConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LateFeeCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $charge  = $this->charge;
        $booking = $charge->booking;
        $amount  = 'Rp ' . number_format($charge->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject('🔔 Tagihan Denda Keterlambatan — ' . $booking->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line('Admin telah mengkonfirmasi tagihan denda keterlambatan untuk booking **' . $booking->code . '**.')
            ->line('**Durasi Terlambat:** ' . $charge->late_hours . ' jam')
            ->line('**Jumlah Denda:** ' . $amount)
            ->line('---')
            ->line('**Rekening Tujuan Pembayaran:**')
            ->line('Bank: ' . ($charge->bank_name ?? '—'))
            ->line('No. Rekening: ' . ($charge->bank_account_no ?? '—'))
            ->line('Atas Nama: ' . ($charge->bank_account_name ?? '—'))
            ->line('---')
            ->line('Silakan transfer denda ke rekening di atas, lalu upload bukti pembayaran melalui tautan berikut.')
            ->action('Upload Bukti Pembayaran Denda', url('/bookings/' . $booking->code))
            ->line('Jika ada keberatan, silakan hubungi admin.')
            ->salutation('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'late_fee_confirmed',
            'booking_id'     => $this->charge->booking_id,
            'booking_code'   => $this->charge->booking?->code,
            'charge_id'      => $this->charge->id,
            'amount'         => $this->charge->amount,
            'late_hours'     => $this->charge->late_hours,
            'message'        => 'Tagihan denda keterlambatan booking '
                . $this->charge->booking?->code
                . ' sebesar Rp ' . number_format($this->charge->amount, 0, ',', '.')
                . ' telah dikonfirmasi. Silakan lakukan pembayaran.',
        ];
    }
}
