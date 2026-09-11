<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateReturnReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'reporter_type',
        'reported_by_user_id',
        'estimated_late_hours',
        'reason',
        'latitude',
        'longitude',
        'location_address',
        'return_latitude',
        'return_longitude',
        'return_location_address',
        'return_confirmed_at',
        'status',
        'acknowledged_at',
        // GPS validation fields (added by migration 2026_07_24_000001)
        'gps_validation_data',
        'return_gps_validation',
        'auto_detection_data',
    ];

    protected $casts = [
        'latitude'             => 'decimal:8',
        'longitude'            => 'decimal:8',
        'return_latitude'      => 'decimal:8',
        'return_longitude'     => 'decimal:8',
        'acknowledged_at'      => 'datetime',
        'return_confirmed_at'  => 'datetime',
        'estimated_late_hours' => 'integer',
        'gps_validation_data'  => 'array',
        'return_gps_validation' => 'array',
        'auto_detection_data'  => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function hasReturnLocation(): bool
    {
        return $this->return_latitude !== null && $this->return_longitude !== null;
    }

    public function reporterLabel(): string
    {
        return match($this->reporter_type) {
            'customer' => 'Customer',
            'driver'   => 'Sopir (via Vendor)',
            'system'   => '🤖 Deteksi Otomatis',
            default    => ucfirst($this->reporter_type),
        };
    }

    /**
     * Google Maps link untuk lokasi laporan
     */
    public function getMapUrl(): ?string
    {
        if (!$this->hasLocation()) return null;
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Google Maps link untuk lokasi pengembalian
     */
    public function getReturnMapUrl(): ?string
    {
        if (!$this->hasReturnLocation()) return null;
        return "https://www.google.com/maps?q={$this->return_latitude},{$this->return_longitude}";
    }
}
