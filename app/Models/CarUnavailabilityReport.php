<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarUnavailabilityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'vendor_id',
        'reason',
        'description',
        'unavailable_from',
        'unavailable_until',
        'status',
        'resolved_at',
        'resolved_notes',
    ];

    protected $casts = [
        'unavailable_from' => 'date',
        'unavailable_until' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
