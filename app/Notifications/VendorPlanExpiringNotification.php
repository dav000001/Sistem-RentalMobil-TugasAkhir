<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorPlanExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Vendor $vendor,
        public int $daysLeft
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planLabel  = $this->vendor->plan->label();
        $expiresAt  = $this->vendor->plan_expires_at->translatedFormat('d F Y');
        $fee        = number_format($this->vendor->plan->monthlyFee(), 0, ',', '.');

        $subject = $this->daysLeft <= 0
            ? "Paket {$planLabel} Anda Telah Berakhir"
            : "Paket {$planLabel} Anda Berakhir dalam {$this->daysLeft} Hari";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . '!');

        if ($this->daysLeft <= 0) {
            $mail->line("Paket **{$planLabel}** Anda telah berakhir pada {$expiresAt}.")
                ->line('Komisi Anda saat ini kembali ke 12% (paket Free).')
                ->line('Segera lakukan pembayaran untuk mengaktifkan kembali paket Anda.');
        } else {
            $mail->line("Paket **{$planLabel}** Anda akan berakhir pada **{$expiresAt}** ({$this->daysLeft} hari lagi).")
                ->line("Biaya perpanjangan: **Rp {$fee}/bulan**")
                ->line('Lakukan pembayaran sebelum paket berakhir agar komisi Anda tetap rendah.');
        }

        return $mail
            ->action('Kelola Paket', url('/vendor/plans'))
            ->line('Hubungi admin jika ada pertanyaan mengenai pembayaran.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'     => 'filament',
            'title'      => $this->daysLeft <= 0 ? '⚠️ Paket Berakhir' : '⏰ Paket Akan Berakhir',
            'body'       => $this->daysLeft <= 0
                ? "Paket {$this->vendor->plan->label()} Anda telah berakhir. Komisi kembali ke 12%."
                : "Paket {$this->vendor->plan->label()} Anda berakhir dalam {$this->daysLeft} hari.",
            'icon'       => 'heroicon-o-credit-card',
            'color'      => $this->daysLeft <= 0 ? 'danger' : 'warning',
            'url'        => url('/vendor/plans'),
            'type'       => 'plan_expiring',
            'vendor_id'  => $this->vendor->id,
            'plan'       => $this->vendor->plan->value,
            'days_left'  => $this->daysLeft,
            'expires_at' => $this->vendor->plan_expires_at?->toDateString(),
        ];
    }
}
