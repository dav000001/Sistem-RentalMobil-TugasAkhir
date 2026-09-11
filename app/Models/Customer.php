<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'ktp_url',
        'sim_url',
        'selfie_url',
        'verification_status',
        'rejection_reason',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'tracking_enabled',
    ];

    protected $casts = [
        'verification_status' => 'string',
        'tracking_enabled'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(CustomerRefund::class);
    }

    public function lateFeeCharges(): HasMany
    {
        return $this->hasMany(LateFeeCharge::class);
    }

    /**
     * Cek apakah customer memiliki denda keterlambatan yang belum dilunasi.
     * Mengembalikan true jika ada LateFeeCharge dengan status selain 'paid' dan 'waived',
     * atau jika terdapat booking ongoing yang sudah melewati batas pengembalian (+ toleransi 60m).
     */
    public function hasUnpaidLateFee(): bool
    {
        $hasUnpaidCharge = $this->lateFeeCharges()
            ->whereNotIn('status', ['paid', 'waived'])
            ->exists();

        if ($hasUnpaidCharge) {
            return true;
        }

        return $this->bookings()
            ->where('status', 'ongoing')
            ->where('end_at', '<', now()->subMinutes(60))
            ->exists();
    }

    /**
     * Ambil record denda belum lunas milik customer.
     */
    public function getUnpaidLateFeeCharge(): ?LateFeeCharge
    {
        return $this->lateFeeCharges()
            ->whereNotIn('status', ['paid', 'waived'])
            ->latest()
            ->first();
    }
}