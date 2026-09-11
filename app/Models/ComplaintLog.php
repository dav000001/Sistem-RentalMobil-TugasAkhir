<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintLog extends Model
{
    protected $fillable = [
        'complaint_id',
        'actor_id',
        'actor_role',
        'action',
        'context',
        'ip_address',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'submitted'   => 'Komplain diajukan',
            'forwarded'   => 'Diteruskan ke vendor',
            'responded'   => 'Respons ditambahkan',
            'resolved'    => 'Komplain diselesaikan',
            'rejected'    => 'Komplain ditolak',
            'escalated'   => 'Komplain dieskalasi',
            default       => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
