<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerContactNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $subject,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'customer_contact',
            'title'        => "✉️ Pesan dari Customer: {$this->senderName}",
            'message'      => "[{$this->subject}] " . \Illuminate\Support\Str::limit($this->message, 100),
            'sender_name'  => $this->senderName,
            'sender_email' => $this->senderEmail,
            'subject'      => $this->subject,
            'full_message' => $this->message,
            'url'          => '/admin',
        ];
    }
}
