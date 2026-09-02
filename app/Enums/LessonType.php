<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Quiz = 'quiz';

    public function label(): string
    {
        return $this === self::Video ? 'Vidéo' : 'Quiz';
    }
}
