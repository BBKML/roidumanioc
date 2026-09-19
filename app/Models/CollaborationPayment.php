<?php

namespace App\Models;

use App\Enums\CollaborationPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollaborationPayment extends Model
{
    protected $fillable = [
        'collaboration_id', 'declared_by', 'amount_declared', 'method', 'note',
        'declared_at', 'confirmed_by', 'confirmed_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_declared' => 'integer',
            'declared_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'status' => CollaborationPaymentStatus::class,
        ];
    }

    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }

    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
