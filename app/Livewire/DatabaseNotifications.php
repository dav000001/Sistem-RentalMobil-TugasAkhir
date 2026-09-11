<?php

namespace App\Livewire;

use Filament\Notifications\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Override Filament DatabaseNotifications agar:
 * 1. Menampilkan semua notifikasi (read + unread), bukan hanya unread
 * 2. Notifikasi unread muncul di atas, read di bawah
 * 3. Notifikasi tidak hilang setelah dibaca
 * 4. Gunakan guard 'admin' agar notifikasi terbaca saat login via admin panel
 */
class DatabaseNotifications extends BaseDatabaseNotifications
{
    public function getUser(): \Illuminate\Contracts\Auth\Authenticatable|null
    {
        // Admin panel pakai guard 'admin', vendor panel pakai guard 'vendor', fallback ke 'web'
        return auth('admin')->user() ?? auth('vendor')->user() ?? auth()->user();
    }

    public function getNotificationsQuery(): Builder | Relation
    {
        $user = $this->getUser();

        if (! $user) {
            abort(401);
        }

        // Tampilkan semua notifikasi: unread dulu, lalu read, diurutkan terbaru
        return $user->notifications()
            ->where('data->format', 'filament')
            ->orderByRaw('read_at IS NOT NULL')   // unread (NULL) naik ke atas
            ->orderByDesc('created_at');
    }
}
