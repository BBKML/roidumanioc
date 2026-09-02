<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteContent extends Model
{
    protected $fillable = ['key', 'label', 'data', 'position'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('site_content'));
        static::deleted(fn () => Cache::forget('site_content'));
    }

    /**
     * Tout le contenu du site sous forme de tableau [key => data], mis en cache.
     */
    public static function payload(): array
    {
        return Cache::rememberForever('site_content', fn () => static::query()
            ->orderBy('position')
            ->get()
            ->mapWithKeys(fn (self $s) => [$s->key => $s->data])
            ->all());
    }

    public static function section(string $key, mixed $default = null): mixed
    {
        return static::payload()[$key] ?? $default;
    }
}
