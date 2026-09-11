<?php

namespace App\Notifications;

use App\Models\LateFeeCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateFeeProofUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly LateFeeCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'late_fee_proof_uploaded',
            'booking_id'   => $this->charge->booking_id,
            'booking_code' => $this->charge->booking?->code,
            'charge_id'    => $this->charge->id,
            'amount'       => $this->charge->amount,
            'message'      => 'Customer ' . $this->charge->customer?->full_name
                . ' telah upload bukti pembayaran denda booking '
                . $this->charge->booking?->code
                . '. Silakan verifikasi.',
        ];
    }
}
