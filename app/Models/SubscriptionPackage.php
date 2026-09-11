<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price_per_month',
        'commission_rate',
        'features',
        'rank',
        'is_active',
        'allow_self_signup',
    ];

    protected $casts = [
        'features'          => 'array',
        'commission_rate'   => 'decimal:2',
        'is_active'         => 'boolean',
        'allow_self_signup' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class, 'package_id');
    }

    public function maxCars(): ?int
    {
        return $this->features['max_cars'] ?? null;
    }

    public function maxPhotos(): ?int
    {
        return $this->features['max_photos'] ?? null;
    }

    public function hasPrioritySearch(): bool
    {
        return (bool) ($this->features['priority_search'] ?? false);
    }

    public function isFree(): bool
    {
        return $this->price_per_month === 0;
    }

    public function commissionPercent(): float
    {
        return (float) $this->commission_rate;
    }

    public function commissionRate(): float
    {
        return $this->commission_rate / 100;
    }
}
