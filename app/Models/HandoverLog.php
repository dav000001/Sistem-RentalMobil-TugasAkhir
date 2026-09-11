<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoverLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'photos',
        'odometer',
        'fuel_level',
        'notes',
        'signed_by_user_id',
    ];

    protected $casts = [
        'photos' => 'array',
        'odometer' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }
}