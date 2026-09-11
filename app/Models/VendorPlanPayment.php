<?php

namespace App\Models;

use App\Enums\VendorPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPlanPayment extends Model
{
    protected $fillable = [
        'vendor_id',
        'plan',
        'amount',
        'status',
        'method',
        'period_start',
        'period_end',
        'paid_at',
        'confirmed_by',
        'notes',
        'reference',
    ];

    protected $casts = [
        'plan'         => VendorPlan::class,
        'amount'       => 'decimal:2',
        'period_start' => 'date',
        'period_end'   => 'date',
        'paid_at'      => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Pembayaran',
            'paid'    => 'Lunas',
            'failed'  => 'Gagal',
            'waived'  => 'Dibebaskan',
            default   => ucfirst($this->status),
        };
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'manual'           => 'Manual (Transfer)',
            'payout_deduction' => 'Potong Payout',
            'transfer'         => 'Transfer Bank',
            'waived'           => 'Dibebaskan Admin',
            default            => ucfirst($this->method),
        };
    }
}
