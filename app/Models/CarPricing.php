<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'daily_price',
        'weekly_price',
        'monthly_price',
        'with_driver_price',
        'fuel_included',
        'delivery_fee_per_km',
    ];

    protected $casts = [
        'daily_price' => 'decimal:2',
        'weekly_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'with_driver_price' => 'decimal:2',
        'fuel_included' => 'boolean',
        'delivery_fee_per_km' => 'decimal:2',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}