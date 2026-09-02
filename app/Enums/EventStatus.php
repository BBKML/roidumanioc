<?php

namespace App\Enums;

enum EventStatus: string
{
    case Planifie = 'planifie';
    case Termine = 'termine';

    public function label(): string
    {
        return $this === self::Planifie ? 'Planifié' : 'Terminé';
    }
}
