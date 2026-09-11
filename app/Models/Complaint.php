<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'reference',
        'booking_id',
        'reporter_id',
        'vendor_id',
        'category_id',
        'severity',
        'status',
        'description',
        'attachments',
        'customer_demand',
        'demanded_refund_amount',
        'customer_demand_note',
        'forwarded_at',
        'vendor_due_at',
        'admin_due_at',
        'resolved_by',
        'resolved_at',
        'auto_flags',
        'admin_notes',
        'escrow_held',
        'ip_address',
    ];

    protected $casts = [
        'attachments'   => 'array',
        'auto_flags'    => 'array',
        'forwarded_at'  => 'datetime',
        'vendor_due_at' => 'datetime',
        'admin_due_at'  => 'datetime',
        'resolved_at'   => 'datetime',
        'escrow_held'   => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ComplaintResponse::class)->orderBy('created_at');
    }

    public function resolution(): HasOne
    {
        return $this->hasOne(ComplaintResolution::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ComplaintLog::class)->orderBy('created_at');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ─── Methods ──────────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->vendor_due_at !== null && $this->vendor_due_at->isPast();
    }

    public function canBeReportedBy(User $user): bool
    {
        $booking = $this->booking;
        if (!$booking) return false;

        // Check via customer relationship
        return $booking->customer && $booking->customer->user_id === $user->id;
    }

    public static function generateReference(): string
    {
        $year  = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;

        return 'CMP-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    // ─── Status helpers ───────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return !in_array($this->status, ['resolved', 'rejected']);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'submitted'            => 'Diajukan',
            'forwarded_to_vendor'  => 'Diteruskan ke Vendor',
            'vendor_responded'     => 'Vendor Merespons',
            'vendor_accepted'      => 'Vendor Menerima',
            'vendor_silent'        => 'Vendor Tidak Merespons',
            'under_admin_review'   => 'Ditinjau Admin',
            'resolved'             => 'Selesai',
            'rejected'             => 'Ditolak',
            'escalated'            => 'Dieskalasi',
            'awaiting_insurance'   => 'Menunggu Asuransi',
            default                => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'submitted'            => 'yellow',
            'forwarded_to_vendor'  => 'blue',
            'vendor_responded'     => 'indigo',
            'vendor_accepted'      => 'teal',
            'vendor_silent'        => 'orange',
            'under_admin_review'   => 'purple',
            'resolved'             => 'green',
            'rejected'             => 'red',
            'escalated'            => 'red',
            'awaiting_insurance'   => 'gray',
            default                => 'gray',
        };
    }

    public function severityLabel(): string
    {
        return match ($this->severity) {
            'low'      => 'Rendah',
            'medium'   => 'Sedang',
            'high'     => 'Tinggi',
            'critical' => 'Kritis',
            default    => ucfirst($this->severity),
        };
    }

    public function severityColor(): string
    {
        return match ($this->severity) {
            'low'      => 'green',
            'medium'   => 'yellow',
            'high'     => 'orange',
            'critical' => 'red',
            default    => 'gray',
        };
    }
}
