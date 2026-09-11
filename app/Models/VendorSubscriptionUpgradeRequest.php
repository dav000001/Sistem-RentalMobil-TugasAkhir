<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSubscriptionUpgradeRequest extends Model
{
    protected $fillable = [
        'vendor_id',
        'current_subscription_id',
        'target_package_id',
        'type',
        'reason',
        'status',
        'reviewed_by',
        'admin_note',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(VendorSubscription::class, 'current_subscription_id');
    }

    public function targetPackage(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'target_package_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'upgrade'         => 'Upgrade Paket',
            'downgrade_early' => 'Downgrade Lebih Awal',
            'cancel_early'    => 'Batalkan Lebih Awal',
            default           => ucfirst($this->type),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'  => 'Menunggu Review',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => ucfirst($this->status),
        };
    }
}
