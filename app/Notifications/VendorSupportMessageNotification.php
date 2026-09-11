<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VendorSupportMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $vendorName,
        public string $vendorEmail,
        public string $subject,
        public string $message,
        public ?int   $vendorId = null,
        public ?array $attachments = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $bodyText = "[{$this->subject}] " . \Illuminate\Support\Str::limit($this->message, 100);
        if ($this->subject === 'Bantuan Upload Mobil') {
            $bodyText .= " (Gambar terlampir)";
        }

        return [
            'format'      => 'filament',
            'title'       => "❓ Pertanyaan dari Vendor: {$this->vendorName}",
            'body'        => $bodyText,
            'icon'        => 'heroicon-o-question-mark-circle',
            'color'       => 'warning',
            'url'         => '/admin/vendor-support-tickets',
            'type'        => 'vendor_support',
            'vendor_name' => $this->vendorName,
            'vendor_email'=> $this->vendorEmail,
            'vendor_id'   => $this->vendorId,
            'subject'     => $this->subject,
            'full_message'=> $this->message,
            'attachments' => $this->attachments,
        ];
    }
}
