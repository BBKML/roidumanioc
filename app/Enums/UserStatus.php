<?php

namespace App\Enums;

enum UserStatus: string
{
    case Actif = 'actif';
    case EnAttente = 'en_attente';
    case Suspendu = 'suspendu';

    public function label(): string
    {
        return match ($this) {
            self::Actif => 'Actif',
            self::EnAttente => 'En attente',
            self::Suspendu => 'Suspendu',
        };
    }

    public function canLogin(): bool
    {
        return $this !== self::Suspendu;
    }
}
