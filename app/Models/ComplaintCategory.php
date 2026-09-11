<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'severity',
        'vendor_sla_hours',
        'auto_flags',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'auto_flags' => 'array',
        'is_active'  => 'boolean',
    ];

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }
}
