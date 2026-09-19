<?php

namespace App\Enums;

enum CropUnit: string
{
    case Kg = 'kg';
    case Sac = 'sac';
    case Tonne = 'tonne';
    case Unite = 'unite';

    public function label(): string
    {
        return match ($this) {
            self::Kg => 'Kg',
            self::Sac => 'Sac',
            self::Tonne => 'Tonne',
            self::Unite => 'Unité',
        };
    }
}
