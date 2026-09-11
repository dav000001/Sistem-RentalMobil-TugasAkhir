<?php

namespace App\Models;

use App\Enums\VendorPlan;
use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
        'address',
        'city_id',
        'ktp_url',
        'npwp_url',
        'siup_url',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'plan',
        'plan_upgraded_at',
        'plan_expires_at',
        'fleet_size_estimate',
        'lead_source',
        'whatsapp_verified_at',
        'documents_submitted_at',
        'last_reviewed_at',
        'internal_notes',
        'documents_complete',
        'documents_verified',
        'current_subscription_id',
    ];

    protected $casts = [
        'status'                 => VendorStatus::class,
        'plan'                   => VendorPlan::class,
        'whatsapp_verified_at'   => 'datetime',
        'documents_submitted_at' => 'datetime',
        'last_reviewed_at'       => 'datetime',
        'plan_upgraded_at'       => 'datetime',
        'plan_expires_at'        => 'datetime',
        'documents_complete'     => 'boolean',
        'documents_verified'     => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(VendorStatusLog::class);
    }

    public function planPayments(): HasMany
    {
        return $this->hasMany(VendorPlanPayment::class);
    }

    public function latestPlanPayment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VendorPlanPayment::class)->latestOfMany();
    }

    public function hasPendingPlanPayment(): bool
    {
        return $this->planPayments()->where('status', 'pending')->exists();
    }

    // ── New Subscription System ──────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class);
    }

    public function currentSubscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VendorSubscription::class, 'current_subscription_id');
    }

    public function upgradeRequests(): HasMany
    {
        return $this->hasMany(VendorSubscriptionUpgradeRequest::class);
    }

    public function isPlanExpired(): bool
    {
        if ($this->plan === VendorPlan::Free) {
            return false;
        }
        return $this->plan_expires_at && $this->plan_expires_at->isPast();
    }

    public function isApproved(): bool
    {
        return $this->status === VendorStatus::Approved;
    }

    /**
     * Komisi efektif — pakai snapshot dari subscription aktif jika ada dan sesuai plan.
     * Fallback ke plan.commissionRate() jika tidak ada subscription aktif.
     */
    public function getCommissionRate(): float
    {
        $sub = $this->currentSubscription;
        if ($sub && $sub->snapshot_commission) {
            // Pastikan snapshot_commission sesuai dengan plan saat ini
            // Jika tidak sesuai, pakai plan.commissionRate()
            $planRate = $this->plan->commissionRate() * 100;
            if ((float) $sub->snapshot_commission === $planRate) {
                return (float) $sub->snapshot_commission / 100;
            }
        }
        return $this->plan->commissionRate();
    }

    public function canAddMoreCars(): bool
    {
        $sub = app(\App\Services\VendorSubscriptionService::class)->getCurrent($this);
        if ($sub && isset($sub->snapshot_features['max_cars'])) {
            $maxCars = $sub->snapshot_features['max_cars'];
            if ($maxCars === -1) return true;
            return $this->cars()->where('status', 'published')->count() < $maxCars;
        }

        $maxCars = $this->plan->maxCars();
        if ($maxCars === null) return true;
        return $this->cars()->where('status', 'published')->count() < $maxCars;
    }

    public function remainingCarSlots(): ?int
    {
        $sub = app(\App\Services\VendorSubscriptionService::class)->getCurrent($this);
        if ($sub && isset($sub->snapshot_features['max_cars'])) {
            $maxCars = $sub->snapshot_features['max_cars'];
            if ($maxCars === -1) return null;
            return max(0, $maxCars - $this->cars()->where('status', 'published')->count());
        }

        $maxCars = $this->plan->maxCars();
        if ($maxCars === null) return null;
        return max(0, $maxCars - $this->cars()->where('status', 'published')->count());
    }
}