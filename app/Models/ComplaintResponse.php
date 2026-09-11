<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintResponse extends Model
{
    protected $fillable = [
        'complaint_id',
        'author_id',
        'author_role',
        'visibility',
        'message',
        'attachments',
        'is_offer',
        'offer_payload',
        'offer_accepted',
    ];

    protected $casts = [
        'attachments'   => 'array',
        'offer_payload' => 'array',
        'is_offer'      => 'boolean',
        'offer_accepted' => 'boolean',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function authorRoleLabel(): string
    {
        return match ($this->author_role) {
            'customer' => 'Pelanggan',
            'vendor'   => 'Vendor',
            'admin'    => 'Admin',
            default    => ucfirst($this->author_role),
        };
    }
}
