<?php

namespace App\Enums;

enum FormationAccess: string
{
    case Gratuit = 'gratuit';
    case Premium = 'premium';

    public function label(): string
    {
        return $this === self::Gratuit ? 'Gratuit' : 'Premium';
    }
}
