<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateFeeCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'vendor_id',
        'amount',
        'late_hours',
        'status',
        'payment_proof',
        'proof_uploaded_at',
        'confirmed_by',
        'confirmed_at',
        'paid_at',
        'admin_notes',
        'waive_reason',
        'dispute_reason',
        'disputed_at',
        'dispute_rejection_reason',
        'dispute_rejected_at',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
    ];

    protected $casts = [
        'amount'                => 'decimal:2',
        'proof_uploaded_at'     => 'datetime',
        'confirmed_at'          => 'datetime',
        'paid_at'               => 'datetime',
        'disputed_at'           => 'datetime',
        'dispute_rejected_at'   => 'datetime',
        'late_hours'            => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'   => '⏳ Menunggu Konfirmasi Admin',
            'confirmed' => '🔔 Menunggu Pembayaran Customer',
            'paid'      => '✅ Sudah Dibayar',
            'waived'    => '🎁 Dibebaskan',
            default     => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'pending'   => 'warning',
            'confirmed' => 'info',
            'paid'      => 'success',
            'waived'    => 'gray',
            default     => 'gray',
        };
    }
}
