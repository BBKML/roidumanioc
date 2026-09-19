<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case Nouveau = 'nouveau';
    case Lu = 'lu';
    case Traite = 'traite';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::Lu => 'Lu',
            self::Traite => 'Traité',
            self::Spam => 'Spam',
        };
    }

    /** Message qui demande encore une action. */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Nouveau, self::Lu], true);
    }
}
