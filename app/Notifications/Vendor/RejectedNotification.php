<?php

namespace App\Notifications\Vendor;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Vendor $vendor,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Pendaftaran Vendor Ditolak — ' . $this->vendor->business_name)
            ->greeting('Halo, ' . $notifiable->name . '.')
            ->line('Mohon maaf, pendaftaran vendor **' . $this->vendor->business_name . '** tidak dapat kami setujui saat ini.')
            ->line('---')
            ->line('**Alasan Penolakan:**')
            ->line($this->reason)
            ->line('---')
            ->line('**Langkah Selanjutnya:**')
            ->line('Jika Anda merasa ada kesalahan atau ingin mengajukan banding, silakan hubungi tim support kami dengan menyertakan nama bisnis dan alasan yang ingin Anda sampaikan.')
            ->line('Anda masih bisa menggunakan platform sebagai penyewa (customer) dengan akun yang sama.')
            ->action('Hubungi Support', url('/hubungi-kami'))
            ->salutation('Terima kasih atas pengertian Anda. — Tim Rental Mobil');
    }

    public function toArray($notifiable): array
    {
        return [
            'vendor_id'   => $this->vendor->id,
            'type'        => 'vendor_rejected',
            'message'     => '❌ Pendaftaran vendor ' . $this->vendor->business_name . ' ditolak.',
            'reason'      => $this->reason,
        ];
    }
}
