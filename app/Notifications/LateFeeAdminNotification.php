<?php

namespace App\Notifications;

use App\Models\LateFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateFeeAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LateFeeCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['database'];  // Admin pakai notifikasi database Filament
    }

    public function toArray(object $notifiable): array
    {
        $booking = $this->charge->booking;
        $amount  = 'Rp ' . number_format($this->charge->amount, 0, ',', '.');

        return [
            'type'           => 'late_fee_pending',
            'booking_id'     => $booking?->id,
            'booking_code'   => $booking?->code,
            'charge_id'      => $this->charge->id,
            'customer_name'  => $this->charge->customer?->full_name,
            'vendor_name'    => $this->charge->vendor?->business_name,
            'late_hours'     => $this->charge->late_hours,
            'amount'         => $this->charge->amount,
            'message'        => '⚠️ Tagihan denda baru: '
                . 'Booking ' . $booking?->code
                . ' — ' . $this->charge->customer?->full_name
                . ' terlambat ' . $this->charge->late_hours . ' jam.'
                . ' Denda ' . $amount . ' menunggu konfirmasi Anda.',
        ];
    }
}
