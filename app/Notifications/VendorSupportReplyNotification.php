<?php

namespace App\Notifications;

use App\Models\VendorSupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke vendor saat admin membalas tiket support.
 */
class VendorSupportReplyNotification extends Notification
{
    use Queueable;

    public function __construct(public VendorSupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'    => 'filament',
            'title'     => '💬 Balasan dari Admin',
            'body'      => 'Tiket "' . $this->ticket->subject . '" telah dibalas oleh admin. '
                         . \Illuminate\Support\Str::limit($this->ticket->admin_reply, 80),
            'icon'      => 'heroicon-o-chat-bubble-left-right',
            'color'     => 'success',
            'url'       => '/vendor/support',
            'ticket_id' => $this->ticket->id,
            'subject'   => $this->ticket->subject,
        ];
    }
}
