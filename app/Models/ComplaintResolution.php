<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintResolution extends Model
{
    protected $fillable = [
        'complaint_id',
        'admin_id',
        'decision',
        'refund_amount',
        'vendor_penalty_amount',
        'voucher_amount',
        'voucher_code',
        'vendor_suspend_days',
        'reasoning',
        'evidence_summary',
        'customer_acknowledged',
        'vendor_acknowledged',
    ];

    protected $casts = [
        'evidence_summary'      => 'array',
        'customer_acknowledged' => 'boolean',
        'vendor_acknowledged'   => 'boolean',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function decisionLabel(): string
    {
        return match ($this->decision) {
            'refund_full'          => 'Refund Penuh',
            'refund_partial'       => 'Refund Sebagian',
            'discount_voucher'     => 'Voucher Diskon',
            'warning_vendor'       => 'Peringatan Vendor',
            'suspend_vendor'       => 'Pembekuan Vendor',
            'rejected'             => 'Ditolak',
            'escalated_legal'      => 'Eskalasi Legal',
            'escalated_insurance'  => 'Eskalasi Asuransi',
            'mutual_agreement'     => 'Kesepakatan Bersama',
            default                => ucfirst(str_replace('_', ' ', $this->decision)),
        };
    }
}
