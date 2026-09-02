<?php

namespace App\Models;

use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $fillable = [
        'formation_id', 'title', 'type', 'duration_label', 'video_url', 'content', 'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'type' => LessonType::class,
        ];
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    public function completedBy(User $user): bool
    {
        return LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $this->id)
            ->exists();
    }
}
