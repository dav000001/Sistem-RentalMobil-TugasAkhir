<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentViewLog extends Model
{
    protected $fillable = [
        'booking_id',
        'customer_id',
        'viewed_by_vendor_id',
        'document_type',
        'viewer_ip',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
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
        return $this->belongsTo(Vendor::class, 'viewed_by_vendor_id');
    }

    /**
     * Catat akses vendor ke dokumen identitas customer.
     */
    public static function record(Booking $booking, ?string $documentType = null): void
    {
        $vendorId = auth('vendor')->user()?->vendor?->id;
        if (!$vendorId) return;

        static::create([
            'booking_id'          => $booking->id,
            'customer_id'         => $booking->customer_id,
            'viewed_by_vendor_id' => $vendorId,
            'document_type'       => $documentType,
            'viewer_ip'           => request()->ip(),
            'viewed_at'           => now(),
        ]);
    }
}
