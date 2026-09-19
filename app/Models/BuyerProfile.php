<?php

namespace App\Models;

use App\Enums\BuyerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuyerProfile extends Model
{
    protected $fillable = ['user_id', 'company_name', 'buyer_type', 'zone', 'bio'];

    protected function casts(): array
    {
        return [
            'buyer_type' => BuyerType::class,
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyerNeeds(): HasMany
    {
        return $this->hasMany(BuyerNeed::class);
    }

    public function connectionRequests(): HasMany
    {
        return $this->hasMany(ConnectionRequest::class);
    }

    public function cropOrders(): HasMany
    {
        return $this->hasMany(CropOrder::class);
    }
}
