<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LessonAttachment extends Model
{
    protected $fillable = ['lesson_id', 'title', 'disk', 'path', 'mime', 'size', 'position'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Supprime le fichier physique quand la ressource est supprimée.
        static::deleted(function (LessonAttachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf'
            || str_ends_with(strtolower($this->path), '.pdf');
    }

    public function downloadUrl(): string
    {
        return route('lessons.attachment', $this);
    }

    public function humanSize(): string
    {
        $bytes = $this->size;

        foreach (['o', 'Ko', 'Mo', 'Go'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, $unit === 'o' ? 0 : 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).' To';
    }
}
