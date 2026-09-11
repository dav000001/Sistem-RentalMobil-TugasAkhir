<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke semua admin saat customer baru berhasil registrasi.
 */
class NewCustomerRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format'    => 'filament',
            'title'     => '👤 Customer Baru Mendaftar',
            'body'      => $this->user->name . ' (' . $this->user->email . ') baru saja mendaftar sebagai customer. '
                         . 'Dokumen identitas menunggu verifikasi.',
            'icon'      => 'heroicon-o-user-plus',
            'color'     => 'info',
            'url'       => url('/admin/users/' . $this->user->id . '/edit'),
            'user_id'   => $this->user->id,
            'user_name' => $this->user->name,
            'user_email'=> $this->user->email,
        ];
    }
}
