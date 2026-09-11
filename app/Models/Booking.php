<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToVendor;class Booking extends Model
{
    use HasFactory, BelongsToVendor;

    /**
     * Relasi yang selalu di-load untuk menghindari N+1 query
     */
    protected $with = [
        'customer.user',
        'vendor.user',
        'car',
        'driver',
        'payment',
    ];

    protected $fillable = [
        'code',
        'customer_id',
        'car_id',
        'vendor_id',
        'driver_id',
        'start_at',
        'end_at',
        'with_driver',
        'pickup_location',
        'dropoff_location',
        'passenger_count',
        'subtotal',
        'addon_fees',
        'discount',
        'total',
        'platform_fee',
        'vendor_payout_amount',
        'status',
        'notes',
        'cancellation_reason',
        'cancelled_at',
        'confirmed_at',
        'picked_up_at',
        'completed_at',
        'actual_return_at',
        'is_late',
        'late_duration_hours',
        'late_fee',
    ];

    protected $casts = [
        'start_at'            => 'datetime',
        'end_at'              => 'datetime',
        'with_driver'         => 'boolean',
        'passenger_count'     => 'integer',
        'subtotal'            => 'decimal:2',
        'addon_fees'          => 'decimal:2',
        'discount'            => 'decimal:2',
        'total'               => 'decimal:2',
        'platform_fee'        => 'decimal:2',
        'vendor_payout_amount'=> 'decimal:2',
        'late_fee'            => 'decimal:2',
        'cancelled_at'        => 'datetime',
        'confirmed_at'        => 'datetime',
        'picked_up_at'        => 'datetime',
        'completed_at'        => 'datetime',
        'actual_return_at'    => 'datetime',
        'is_late'             => 'boolean',
        'late_duration_hours' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function handoverLogs(): HasMany
    {
        return $this->hasMany(HandoverLog::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(CustomerRefund::class);
    }

    public function compensationCharge(): HasOne
    {
        return $this->hasOne(CompensationCharge::class);
    }

    public function lateFeeCharge(): HasOne
    {
        return $this->hasOne(LateFeeCharge::class);
    }

    public function lateReturnReport(): HasOne
    {
        return $this->hasOne(LateReturnReport::class);
    }

    public function carChangeRequest(): HasOne
    {
        return $this->hasOne(CarChangeRequest::class);
    }

    public function trackingPoints(): HasMany
    {
        return $this->hasMany(BookingTrackingPoint::class);
    }

    public function emergencyReports(): HasMany
    {
        return $this->hasMany(EmergencyReport::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(BookingRecommendation::class, 'rejected_booking_id');
    }

    /**
     * Buat refund record pending jika booking dibatalkan dan sudah ada pembayaran yang dikonfirmasi.
     */
    public function createRefundIfNeeded(): void
    {
        // Hanya buat refund jika payment sudah paid dan belum ada refund record
        if (
            $this->payment?->status === 'paid' &&
            ! $this->refund()->whereIn('status', ['pending', 'paid'])->exists()
        ) {
            $customer = $this->customer;
            CustomerRefund::create([
                'booking_id'        => $this->id,
                'customer_id'       => $this->customer_id,
                'amount'            => $this->payment->amount,
                'status'            => 'pending',
                'bank_name'         => $customer?->bank_name,
                'bank_account_no'   => $customer?->bank_account_no,
                'bank_account_name' => $customer?->bank_account_name,
            ]);
        }
    }
}