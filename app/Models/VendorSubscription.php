<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToVendor;

class VendorSubscription extends Model
{
    use SoftDeletes, BelongsToVendor;

    protected $fillable = [
        'uuid',
        'vendor_id',
        'package_id',
        'status',
        'started_at',
        'expires_at',
        'grace_until',
        'amount_paid',
        'proration_credit',
        'payment_method',
        'payment_reference',
        'paid_at',
        'auto_renew',
        'snapshot_features',
        'snapshot_commission',
        'cancel_reason',
        'cancelled_by',
        'payment_proof',
        'transfer_ref',
    ];

    protected $casts = [
        'started_at'          => 'datetime',
        'expires_at'          => 'datetime',
        'grace_until'         => 'datetime',
        'paid_at'             => 'datetime',
        'auto_renew'          => 'boolean',
        'snapshot_features'   => 'array',
        'snapshot_commission' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VendorSubscriptionLog::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInGracePeriod(): bool
    {
        return $this->status === 'grace_period';
    }

    public function isExpiredLocked(): bool
    {
        return $this->status === 'expired_locked';
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function canAcceptBookings(): bool
    {
        return in_array($this->status, ['active', 'grace_period']);
    }

    public function daysRemaining(): int
    {
        if (!$this->expires_at) return 0;
        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function progressPercent(): int
    {
        if (!$this->started_at || !$this->expires_at) return 0;
        $total = $this->started_at->diffInDays($this->expires_at);
        if ($total === 0) return 100;
        $elapsed = $this->started_at->diffInDays(now());
        return min(100, (int) round(($elapsed / $total) * 100));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_payment'    => 'Menunggu Pembayaran',
            'active'             => 'Aktif',
            'grace_period'       => 'Grace Period',
            'expired_locked'     => 'Expired',
            'cancelled_admin'    => 'Dibatalkan Admin',
            'cancelled_by_upgrade' => 'Diupgrade',
            default              => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active'             => 'success',
            'grace_period'       => 'warning',
            'pending_payment'    => 'info',
            'expired_locked'     => 'danger',
            'cancelled_admin'    => 'gray',
            'cancelled_by_upgrade' => 'gray',
            default              => 'gray',
        };
    }

    /**
     * Komisi efektif — pakai snapshot jika ada, fallback ke package master.
     */
    public function effectiveCommissionRate(): float
    {
        if ($this->snapshot_commission) {
            return (float) $this->snapshot_commission / 100;
        }
        return $this->package->commissionRate();
    }
}
