<?php

namespace App\Notifications;

use App\Models\CompensationCharge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompensationProofUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(public CompensationCharge $charge) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $booking  = $this->charge->booking;
        $amount   = 'Rp ' . number_format($this->charge->amount, 0, ',', '.');
        $customer = $this->charge->customer?->full_name ?? '-';

        return [
            'format'     => 'filament',
            'title'      => '💰 Bukti Kompensasi Masuk - ' . $amount,
            'body'       => 'Customer ' . $customer
                          . ' sudah upload bukti bayar untuk booking '
                          . ($booking?->code ?? '-')
                          . '. Segera verifikasi.',
            'icon'       => 'heroicon-o-receipt-percent',
            'color'      => 'warning',
            'url'        => '/admin/compensation-charges/' . $this->charge->id . '/edit',
        ];
    }
}
