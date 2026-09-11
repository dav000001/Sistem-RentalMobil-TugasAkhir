<?php

namespace App\Notifications;

use App\Models\CarUnavailabilityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CarUnavailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CarUnavailabilityReport $report
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $vendorName = $this->report->vendor->business_name ?? 'Vendor';
        $carName    = $this->report->car->brand . ' ' . $this->report->car->model;
        $reason     = match ($this->report->reason) {
            'service'    => 'Service / Perawatan',
            'rusak'      => 'Rusak',
            'kecelakaan' => 'Kecelakaan',
            'lainnya'    => 'Lainnya',
            default      => ucfirst($this->report->reason),
        };

        return [
            'format'    => 'filament',
            'title'     => '🚗 Mobil Tidak Tersedia',
            'body'      => "{$vendorName} melaporkan bahwa {$carName} tidak tersedia. Alasan: {$reason}.",
            'icon'      => 'heroicon-o-exclamation-triangle',
            'color'     => 'warning',
            'url'       => url('/admin/cars'),
            'report_id' => $this->report->id,
            'car_id'    => $this->report->car_id,
            'vendor_id' => $this->report->vendor_id,
        ];
    }
}
