<?php

namespace App\Models;

use App\Enums\BuyerNeedStatus;
use App\Enums\CropUnit;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyerNeed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'buyer_profile_id', 'product_wanted', 'quantity', 'unit', 'location',
        'wanted_date', 'frequency', 'quality_desc', 'budget_indicative',
        'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit' => CropUnit::class,
            'wanted_date' => 'date',
            'budget_indicative' => 'integer',
            'status' => BuyerNeedStatus::class,
        ];
    }

    public function buyerProfile(): BelongsTo
    {
        return $this->belongsTo(BuyerProfile::class);
    }

    /** Visible publiquement : statut ouvert, propriétaire actif (pas suspendu). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', BuyerNeedStatus::Ouvert)
            ->whereHas('buyerProfile.user', fn (Builder $q) => $q->where('status', UserStatus::Actif));
    }
}
