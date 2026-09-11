<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = [
        'user_id',
        'sender_name',
        'sender_email',
        'subject',
        'message',
        'admin_reply',
        'replied_by',
        'replied_at',
        'is_read_by_customer',
    ];

    protected $casts = [
        'replied_at'           => 'datetime',
        'is_read_by_customer'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
