<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke admin saat vendor baru submit dokumen untuk verifikasi.
 * Menggunakan format 'filament' agar muncul di bell icon panel admin.
 */
class VendorSubmittedDocumentsNotification extends Notification
{
    public function __construct(public Vendor $vendor) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $businessTypeLabels = [
            'perorangan' => 'Perorangan',
            'cv'         => 'CV',
            'pt'         => 'PT',
            'komunitas'  => 'Komunitas',
        ];

        $typeLabel = $businessTypeLabels[$this->vendor->business_type ?? ''] ?? 'Badan Usaha';

        return [
            'format'    => 'filament',
            'title'     => '🏪 Verifikasi Vendor Baru',
            'body'      => $this->vendor->business_name
                         . ' (' . $typeLabel . ')'
                         . ' mengajukan dokumen verifikasi. Silakan review.',
            'icon'      => 'heroicon-o-building-storefront',
            'color'     => 'warning',
            'url'       => url('/admin/vendors/' . $this->vendor->id),
            'vendor_id' => $this->vendor->id,
        ];
    }
}
