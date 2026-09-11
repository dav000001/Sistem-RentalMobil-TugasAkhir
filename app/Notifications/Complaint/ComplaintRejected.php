<?php

namespace App\Notifications\Complaint;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintRejected extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'        => 'Komplain Ditolak',
            'message'      => 'Komplain dengan referensi ' . $this->complaint->reference . ' telah ditolak. Alasan: ' . $this->reason,
            'complaint_id' => $this->complaint->id,
            'reference'    => $this->complaint->reference,
            'reason'       => $this->reason,
        ];
    }
}
