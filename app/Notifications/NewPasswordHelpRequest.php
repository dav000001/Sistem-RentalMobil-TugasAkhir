<?php

namespace App\Notifications;

use App\Models\PasswordHelpRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewPasswordHelpRequest extends Notification
{
    use Queueable;

    public function __construct(
        public PasswordHelpRequest $helpRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Hanya database — muncul di lonceng Filament
    }

    public function toArray(object $notifiable): array
    {
        $name = $this->helpRequest->business_name ?? $this->helpRequest->email;

        return [
            'title'   => '🔐 Permintaan Bantuan Password',
            'body'    => "Dari: {$name} ({$this->helpRequest->email})",
            'reason'  => $this->helpRequest->reason,
            'email'   => $this->helpRequest->email,
            'request_id' => $this->helpRequest->id,
        ];
    }
}
