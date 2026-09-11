<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends Model
{
    protected $fillable = [
        'vendor_id',
        'type',
        'path',
        'original_name',
        'size',
        'mime',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Dokumen wajib berdasarkan tipe bisnis vendor.
     * - perorangan           : KTP, NPWP, Selfie+KTP
     * - cv, pt, komunitas    : KTP, NPWP, SIUP/NIB, Selfie+KTP
     * - null/lainnya         : default ke badan_usaha (backward-compatible)
     */
    public static function requiredTypes(string $businessType = 'badan_usaha'): array
    {
        if ($businessType === 'perorangan') {
            return ['ktp', 'npwp', 'selfie'];
        }

        // cv, pt, komunitas, badan_usaha
        return ['ktp', 'npwp', 'siup', 'selfie'];
    }

    /**
     * Dokumen opsional berdasarkan tipe bisnis.
     * - perorangan : SIUP/NIB boleh diupload tapi tidak diwajibkan
     * - lainnya    : tidak ada dokumen opsional
     */
    public static function optionalTypes(string $businessType = 'badan_usaha'): array
    {
        if ($businessType === 'perorangan') {
            return ['siup'];
        }

        return [];
    }

    /**
     * Semua tipe dokumen yang diizinkan untuk diupload (wajib + opsional).
     */
    public static function allowedTypes(string $businessType = 'badan_usaha'): array
    {
        return array_merge(
            self::requiredTypes($businessType),
            self::optionalTypes($businessType)
        );
    }

    /**
     * Label tipe bisnis yang tampil ke user.
     */
    public static function businessTypeLabel(string $businessType): string
    {
        return match($businessType) {
            'perorangan' => 'Perorangan',
            'cv'         => 'CV (Commanditaire Vennootschap)',
            'pt'         => 'PT (Perseroan Terbatas)',
            'komunitas'  => 'Komunitas',
            default      => 'Badan Usaha',
        };
    }
}
