<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'phone',
        'photo',
        'license_photo',
        'status',
        'experience_years',
        'notes',
    ];

    protected $casts = [
        'experience_years' => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Cek apakah sopir tersedia di rentang tanggal tertentu
     * (tidak ada booking aktif yang overlap)
     */
    public function isAvailableOn(Carbon $start, Carbon $end, ?int $excludeBookingId = null): bool
    {
        $query = $this->bookings()
            ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q2) use ($start, $end) {
                    $q2->where('start_at', '<=', $end)
                       ->where('end_at', '>=', $start);
                });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->doesntExist();
    }

    /**
     * Scope hanya sopir aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
