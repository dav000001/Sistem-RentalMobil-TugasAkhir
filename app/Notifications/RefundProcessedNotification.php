<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public ?string $refundRef = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount   = 'Rp ' . number_format($this->booking->payment->amount, 0, ',', '.');
        $customer = $this->booking->customer;

        $mail = (new MailMessage)
            ->subject('Refund Diproses - ' . $this->booking->code)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line("Refund untuk booking **{$this->booking->code}** sebesar **{$amount}** telah diproses oleh admin.")
            ->line('Pesanan: ' . $this->booking->car->brand . ' ' . $this->booking->car->model);

        if ($customer->bank_account_no) {
            $mail->line("Dana dikirim ke: **{$customer->bank_name}** {$customer->bank_account_no} a.n. {$customer->bank_account_name}");
        }

        if ($this->refundRef) {
            $mail->line("Nomor referensi transfer: **{$this->refundRef}**");
        }

        return $mail
            ->line('Dana biasanya masuk dalam 1–3 hari kerja tergantung bank.')
            ->line('Hubungi kami jika dana belum diterima setelah 3 hari kerja.')
            ->action('Lihat Detail Pesanan', route('bookings.show', $this->booking));
    }

    public function toArray(object $notifiable): array
    {
        $amount = 'Rp ' . number_format($this->booking->payment->amount, 0, ',', '.');

        return [
            'format'       => 'filament',
            'title'        => '💸 Refund Sudah Diproses',
            'body'         => "Refund {$amount} untuk booking {$this->booking->code} sudah dikirim ke rekening Anda."
                            . ($this->refundRef ? " Ref: {$this->refundRef}" : ''),
            'icon'         => 'heroicon-o-banknotes',
            'color'        => 'success',
            'url'          => route('bookings.show', $this->booking),
            'booking_id'   => $this->booking->id,
            'booking_code' => $this->booking->code,
            'amount'       => $this->booking->payment->amount,
        ];
    }
}
