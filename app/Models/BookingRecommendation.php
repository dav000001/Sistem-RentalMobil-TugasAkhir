<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'rejected_booking_id',
        'recommended_car_id',
        'customer_id',
        'rank',
        'score',
        'clicked',
        'clicked_at',
    ];

    protected $casts = [
        'clicked'    => 'boolean',
        'clicked_at' => 'datetime',
        'score'      => 'decimal:2',
    ];

    public function rejectedBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'rejected_booking_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'recommended_car_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Tandai rekomendasi sebagai diklik oleh customer.
     */
    public function markAsClicked(): void
    {
        $this->update([
            'clicked'    => true,
            'clicked_at' => now(),
        ]);
    }
}
