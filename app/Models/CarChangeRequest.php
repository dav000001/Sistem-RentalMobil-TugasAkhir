<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'vendor_id',
        'old_car_id',
        'new_car_id',
        'passenger_count',
        'with_driver',
        'reason',
        'status',
        'original_total',
        'new_total',
        'price_difference',
        'additional_payment_proof',
        'additional_payment_at',
        'vendor_notes',
        'admin_notes',
        'requested_at',
        'responded_at',
        'approved_at',
        'vendor_penalty_amount',
        'platform_penalty_amount',
        'vendor_payout_id',
    ];

    protected $casts = [
        'original_total'          => 'decimal:2',
        'new_total'               => 'decimal:2',
        'price_difference'        => 'decimal:2',
        'requested_at'            => 'datetime',
        'responded_at'            => 'datetime',
        'approved_at'             => 'datetime',
        'additional_payment_at'    => 'datetime',
        'with_driver'             => 'boolean',
        'vendor_penalty_amount'   => 'decimal:2',
        'platform_penalty_amount' => 'decimal:2',
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

    public function vendorPayout(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payout::class, 'vendor_payout_id');
    }

    public function oldCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'old_car_id');
    }

    public function newCar(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'new_car_id');
    }

    /** Apakah permintaan masih bisa dibatalkan oleh customer */
    public function isCancellable(): bool
    {
        return $this->status === 'pending';
    }

    /** Apakah perlu pembayaran selisih */
    public function requiresAdditionalPayment(): bool
    {
        return $this->status === 'approved'
            && $this->price_difference > 0
            && is_null($this->additional_payment_at);
    }

    /** Status label untuk tampilan */
    public function statusLabel(): string
    {
        return match($this->status) {
            'pending'              => '⏳ Menunggu Respon Vendor',
            'approved'             => '✅ Disetujui',
            'rejected'             => '❌ Ditolak (Booking Dibatalkan)',
            'cancelled_by_customer'=> '🚫 Dibatalkan Customer',
            default                => ucfirst($this->status),
        };
    }
}
