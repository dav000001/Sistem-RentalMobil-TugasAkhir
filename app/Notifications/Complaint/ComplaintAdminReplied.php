<?php

namespace App\Notifications\Complaint;

use App\Models\Complaint;
use App\Models\ComplaintResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintAdminReplied extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly ComplaintResponse $response
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function getUrl(object $notifiable): string
    {
        $isVendorReport = $this->complaint->reporter?->vendor !== null;
        $isReporter     = $notifiable->id === $this->complaint->reporter_id;

        if ($isVendorReport && $isReporter) {
            // Vendor pelapor -> halaman laporan vendor
            return url('/vendor/my-reports/' . $this->complaint->id);
        } elseif (!$isVendorReport && $notifiable->vendor) {
            // Vendor yang terkena komplain customer
            return url('/vendor/complaints/' . $this->complaint->id);
        } else {
            // Customer pelapor
            return route('complaints.show', $this->complaint->id);
        }
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isVendorReport = $this->complaint->reporter?->vendor !== null;
        $subject = $isVendorReport
            ? 'Admin membalas laporan Anda - ' . $this->complaint->reference
            : 'Admin membalas komplain - ' . $this->complaint->reference;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Admin telah mengirim respons pada ' . ($isVendorReport ? 'laporan' : 'komplain') . ' ' . $this->complaint->reference . ':')
            ->line($this->response->message)
            ->action('Lihat Detail', $this->getUrl($notifiable))
            ->line('Silakan login untuk melihat dan membalas respons tersebut.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'       => 'filament',
            'title'        => 'Admin membalas - ' . $this->complaint->reference,
            'body'         => \Illuminate\Support\Str::limit($this->response->message, 80),
            'icon'         => 'heroicon-o-chat-bubble-left-right',
            'color'        => 'info',
            'url'          => $this->getUrl($notifiable),
            'complaint_id' => $this->complaint->id,
            'reference'    => $this->complaint->reference,
        ];
    }
}
