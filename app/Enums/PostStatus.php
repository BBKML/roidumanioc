<?php

namespace App\Enums;

enum PostStatus: string
{
    case Visible = 'visible';
    case Masque = 'masque';
    case Signale = 'signale';

    public function label(): string
    {
        return match ($this) {
            self::Visible => 'Visible',
            self::Masque => 'Masqué',
            self::Signale => 'Signalé',
        };
    }
}
