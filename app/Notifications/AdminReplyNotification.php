<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminReplyNotification extends Notification
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'  => 'filament',
            'title'   => '💬 Admin membalas pesan Anda',
            'body'    => 'Subjek: ' . $this->contactMessage->subject . ' — ' .
                         \Illuminate\Support\Str::limit($this->contactMessage->admin_reply, 80),
            'icon'    => 'heroicon-o-chat-bubble-left-right',
            'color'   => 'info',
            'url'     => route('contact.messages'),
            'type'    => 'admin_reply',
            'subject' => $this->contactMessage->subject,
            'reply'   => $this->contactMessage->admin_reply,
        ];
    }
}
