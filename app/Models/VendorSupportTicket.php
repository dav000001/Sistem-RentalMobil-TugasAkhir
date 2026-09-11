<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSupportTicket extends Model
{
    protected $fillable = [
        'vendor_id',
        'subject',
        'message',
        'attachments',
        'status',
        'admin_reply',
        'replied_by',
        'replied_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'replied_at'  => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function hasReply(): bool
    {
        return !is_null($this->admin_reply);
    }
}
