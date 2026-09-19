<?php

namespace App\Models;

use App\Enums\CropOfferStatus;
use App\Enums\CropUnit;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CropOffer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'producer_profile_id', 'product_name', 'variety', 'quantity', 'unit',
        'price_indicative', 'location', 'is_available', 'available_from',
        'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit' => CropUnit::class,
            'price_indicative' => 'integer',
            'is_available' => 'boolean',
            'available_from' => 'date',
            'status' => CropOfferStatus::class,
        ];
    }

    public function producerProfile(): BelongsTo
    {
        return $this->belongsTo(ProducerProfile::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CropOfferPhoto::class)->orderBy('position');
    }

    public function cropOrders(): HasMany
    {
        return $this->hasMany(CropOrder::class);
    }

    /** Visible publiquement : statut publiée, en stock, propriétaire actif (pas suspendu). */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CropOfferStatus::Publiee)
            ->where('is_available', true)
            ->whereHas('producerProfile.user', fn (Builder $q) => $q->where('status', UserStatus::Actif));
    }
}
