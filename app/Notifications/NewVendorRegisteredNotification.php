<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke semua admin saat vendor baru berhasil registrasi.
 */
class NewVendorRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $businessName = $this->user->vendor?->business_name ?? '—';

        return [
            'format'        => 'filament',
            'title'         => '🏢 Vendor Baru Mendaftar',
            'body'          => $this->user->name . ' (' . $this->user->email . ') mendaftarkan bisnis "'
                             . $businessName . '". Dokumen menunggu persetujuan admin.',
            'icon'          => 'heroicon-o-building-storefront',
            'color'         => 'warning',
            'url'           => url('/admin/users/' . $this->user->id . '/edit'),
            'user_id'       => $this->user->id,
            'user_name'     => $this->user->name,
            'user_email'    => $this->user->email,
            'business_name' => $businessName,
        ];
    }
}
