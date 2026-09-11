<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToVendor;

class Car extends Model
{
    use HasFactory, BelongsToVendor;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'city_id',
        'car_brand_id',
        'car_model_id',
        'brand',
        'model',
        'year',
        'plate_number',
        'transmission',
        'fuel',
        'seats',
        'luggage',
        'features',
        'description',
        'status',
        'rental_option',
        'is_monthly_available',
        'unavailability_reason',
        'unavailability_notes',
        'unavailable_until',
        'slug',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_address',
    ];

    protected $casts = [
        'features'             => 'array',
        'year'                 => 'integer',
        'seats'                => 'integer',
        'luggage'              => 'integer',
        'unavailable_until'    => 'date',
        'is_monthly_available' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function carBrand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class);
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CarPhoto::class);
    }

    public function pricing(): HasOne
    {
        return $this->hasOne(CarPricing::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(CarAvailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        // Exclude unavailable — mobil sedang service/rusak tidak tampil ke customer
        return $query->where('cars.status', 'published');
    }

    /**
     * Scope untuk mobil yang benar-benar tersedia untuk customer
     * (published + tidak sedang service/rusak)
     */
    public function scopeAvailableForCustomer($query)
    {
        return $query->where('cars.status', 'published')
            ->where(function ($q) {
                $q->whereNull('cars.unavailability_reason')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('cars.unavailable_until')
                         ->where('cars.unavailable_until', '<', now()->toDateString());
                  });
            });
    }

    /**
     * Cek apakah mobil sedang unavailable (service/rusak/dll)
     */
    public function isCurrentlyUnavailable(): bool
    {
        if (is_null($this->unavailability_reason)) {
            return false;
        }

        // Jika ada unavailable_until, cek apakah masih dalam periode
        if (!is_null($this->unavailable_until)) {
            return $this->unavailable_until >= now()->toDateString();
        }

        // Jika tidak ada unavailable_until, berarti unavailable indefinitely
        return true;
    }

    /**
     * Cek apakah mobil tersedia pada rentang tanggal tertentu
     */
    public function isAvailableOn(?\Carbon\Carbon $startAt = null, ?\Carbon\Carbon $endAt = null): bool
    {
        $query = $this->bookings()
            ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing']);

        if ($startAt && $endAt) {
            $query->where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('start_at', [$startAt, $endAt])
                  ->orWhereBetween('end_at', [$startAt, $endAt])
                  ->orWhere(function ($q2) use ($startAt, $endAt) {
                      $q2->where('start_at', '<=', $startAt)
                         ->where('end_at', '>=', $endAt);
                  });
            });
        }

        return $query->doesntExist();
    }

    /**
     * Cek apakah mobil sedang aktif disewa sekarang
     */
    public function isCurrentlyBooked(): bool
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'ongoing'])
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->exists();
    }

    /**
     * Ambil booking aktif saat ini.
     * Jika relasi 'bookings' sudah di-eager load, gunakan collection (hindari N+1).
     */
    public function activeBooking()
    {
        if ($this->relationLoaded('bookings')) {
            return $this->bookings
                ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
                ->where('end_at', '>=', now())
                ->sortBy('start_at')
                ->first();
        }

        return $this->bookings()
            ->whereIn('status', ['awaiting_vendor', 'confirmed', 'ongoing'])
            ->where('end_at', '>=', now())
            ->orderBy('start_at')
            ->first();
    }
}