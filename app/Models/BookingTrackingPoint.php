<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTrackingPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'latitude',
        'longitude',
        'address',
        'accuracy_score',
        'gps_validation_data',
        'metadata',
        'recorded_at'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy_score' => 'integer',
        'gps_validation_data' => 'array',
        'metadata' => 'array',
        'recorded_at' => 'datetime'
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get Google Maps URL for this point
     */
    public function getMapUrl(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Check if this point has high GPS accuracy
     */
    public function hasHighAccuracy(): bool
    {
        return $this->accuracy_score >= 80;
    }

    /**
     * Get human readable accuracy status
     */
    public function getAccuracyStatus(): string
    {
        return match(true) {
            $this->accuracy_score >= 80 => 'Sangat Akurat',
            $this->accuracy_score >= 60 => 'Cukup Akurat', 
            $this->accuracy_score >= 40 => 'Kurang Akurat',
            default => 'Tidak Akurat'
        };
    }

    /**
     * Get accuracy color for UI
     */
    public function getAccuracyColor(): string
    {
        return match(true) {
            $this->accuracy_score >= 80 => 'green',
            $this->accuracy_score >= 60 => 'yellow',
            $this->accuracy_score >= 40 => 'orange',
            default => 'red'
        };
    }

    /**
     * Scope for high accuracy points only
     */
    public function scopeHighAccuracy($query)
    {
        return $query->where('accuracy_score', '>=', 80);
    }

    /**
     * Scope for recent points
     */
    public function scopeRecent($query, int $hours = 4)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for points within date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }
}