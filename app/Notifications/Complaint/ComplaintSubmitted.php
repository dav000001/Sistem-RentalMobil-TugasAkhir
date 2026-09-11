<?php

namespace App\Notifications\Complaint;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Complaint $complaint
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'        => 'Komplain Baru Masuk',
            'message'      => 'Komplain baru dengan referensi ' . $this->complaint->reference . ' telah diajukan oleh pelanggan.',
            'complaint_id' => $this->complaint->id,
            'reference'    => $this->complaint->reference,
            'severity'     => $this->complaint->severity,
            'category'     => $this->complaint->category?->name,
        ];
    }
}
