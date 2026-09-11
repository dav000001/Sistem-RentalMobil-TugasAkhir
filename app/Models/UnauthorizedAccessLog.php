<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnauthorizedAccessLog extends Model
{
    protected $fillable = [
        'user_id',
        'attempted_resource',
        'attempted_id',
        'owner_vendor_id',
        'action',
        'route',
        'ip',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hitung jumlah percobaan dalam 24 jam terakhir untuk user tertentu.
     */
    public static function countRecentAttempts(int $userId, int $hours = 24): int
    {
        return static::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }
}
