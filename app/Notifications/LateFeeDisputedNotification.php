<?php

namespace App\Notifications;

use App\Models\LateFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateFeeDisputedNotification extends Notification implements ShouldQueue
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
        $customerName = $this->charge->customer?->full_name ?? 'Customer';

        return (new MailMessage)
            ->subject('⚠️ Keberatan Denda Keterlambatan — ' . $booking?->code)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line("Customer **{$customerName}** mengajukan keberatan/banding atas denda keterlambatan booking **{$booking?->code}**.")
            ->line('**Alasan Keberatan:** ' . ($this->charge->dispute_reason ?? '—'))
            ->line('**Jumlah Denda:** Rp ' . number_format($this->charge->amount, 0, ',', '.'))
            ->action('Tinjau Tagihan Denda', url('/admin/late-fee-charges'))
            ->salutation('Tim Rental Mobil');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'late_fee_disputed',
            'booking_id'   => $this->charge->booking_id,
            'booking_code' => $this->charge->booking?->code,
            'charge_id'    => $this->charge->id,
            'amount'       => $this->charge->amount,
            'reason'       => $this->charge->dispute_reason,
            'message'      => 'Customer mengajukan keberatan denda untuk booking ' . $this->charge->booking?->code,
        ];
    }
}
