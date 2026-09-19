<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'source', 'unsubscribed_at'];

    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return is_null($this->unsubscribed_at);
    }

    public function unsubscribe(): void
    {
        $this->update(['unsubscribed_at' => now()]);
    }

    public function resubscribe(): void
    {
        $this->update(['unsubscribed_at' => null]);
    }
}
