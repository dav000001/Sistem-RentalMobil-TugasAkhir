<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'customer_id', 
        'vendor_id',
        'type',
        'description',
        'latitude',
        'longitude',
        'status',
        'resolution',
        'reported_at',
        'resolved_at'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime'
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

    /**
     * Generate unique emergency code for reference
     */
    public function generateEmergencyCode(): string
    {
        return 'EMR-' . $this->id . '-' . $this->reported_at->format('ymd');
    }

    /**
     * Get emergency type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'breakdown' => '🔧 Kerusakan Kendaraan',
            'accident' => '💥 Kecelakaan',
            'theft' => '🚨 Pencurian',
            'harassment' => '⚠️ Pelecehan/Gangguan',
            'medical' => '🏥 Darurat Medis',
            'other' => '📞 Lainnya',
            default => ucfirst($this->type)
        };
    }

    /**
     * Get status label with color
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => '🔴 Aktif - Perlu Penanganan',
            'responding' => '🟡 Sedang Ditangani',
            'resolved' => '🟢 Selesai',
            'closed' => '⚫ Ditutup',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'red',
            'responding' => 'yellow',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get priority level based on type
     */
    public function getPriorityLevel(): string
    {
        return match($this->type) {
            'medical', 'accident' => 'critical',
            'theft', 'harassment' => 'high',
            'breakdown' => 'medium',
            'other' => 'low',
            default => 'medium'
        };
    }

    /**
     * Get priority color
     */
    public function getPriorityColor(): string
    {
        return match($this->getPriorityLevel()) {
            'critical' => '#dc2626', // red-600
            'high' => '#ea580c',     // orange-600  
            'medium' => '#d97706',   // amber-600
            'low' => '#65a30d',      // lime-600
            default => '#6b7280'     // gray-500
        };
    }

    /**
     * Get Google Maps URL
     */
    public function getMapUrl(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Check if emergency is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'responding']);
    }

    /**
     * Check if emergency is resolved
     */
    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Calculate response time in minutes
     */
    public function getResponseTimeMinutes(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }

        return $this->reported_at->diffInMinutes($this->resolved_at);
    }

    /**
     * Get response time formatted
     */
    public function getResponseTimeFormatted(): ?string
    {
        $minutes = $this->getResponseTimeMinutes();
        
        if ($minutes === null) {
            return null;
        }

        if ($minutes < 60) {
            return "{$minutes} menit";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours} jam";
        }

        return "{$hours} jam {$remainingMinutes} menit";
    }

    /**
     * Scope for active emergencies
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'responding']);
    }

    /**
     * Scope for resolved emergencies
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    /**
     * Scope for high priority emergencies
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('type', ['medical', 'accident', 'theft', 'harassment']);
    }

    /**
     * Scope for recent emergencies
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('reported_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}