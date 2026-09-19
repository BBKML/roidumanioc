<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Document = 'document';
    case Quiz = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Vidéo',
            self::Document => 'Document (PDF)',
            self::Quiz => 'Quiz',
        };
    }
}
