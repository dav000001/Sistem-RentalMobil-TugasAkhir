<?php

namespace App\Notifications\Complaint;

use App\Models\Complaint;
use App\Models\ComplaintResolution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintResolved extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Complaint $complaint,
        public readonly ComplaintResolution $resolution
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'        => 'Komplain Telah Diselesaikan',
            'message'      => 'Komplain dengan referensi ' . $this->complaint->reference . ' telah diselesaikan dengan keputusan: ' . $this->resolution->decisionLabel() . '.',
            'complaint_id' => $this->complaint->id,
            'reference'    => $this->complaint->reference,
            'decision'     => $this->resolution->decision,
        ];
    }
}
