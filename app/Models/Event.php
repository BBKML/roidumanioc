<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title', 'date_label', 'starts_at', 'type', 'status', 'link', 'description', 'position',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'status' => EventStatus::class,
            'position' => 'integer',
        ];
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Planifie)->orderBy('position');
    }
}
