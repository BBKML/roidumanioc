<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Apprenant = 'apprenant';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Apprenant => 'Apprenant',
        };
    }

    public function isStaff(): bool
    {
        return $this === self::Admin;
    }
}
