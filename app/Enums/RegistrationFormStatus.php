<?php

namespace App\Enums;

enum RegistrationFormStatus: string
{
    case Brouillon = 'brouillon';
    case Publiee = 'publiee';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Publiee => 'Publié',
        };
    }
}
