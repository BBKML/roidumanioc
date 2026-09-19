<?php

namespace App\Support;

/**
 * Normalise les numéros de téléphone en un format canonique (chiffres uniquement,
 * indicatif Côte d'Ivoire +225/00225 retiré) afin qu'ils puissent servir
 * d'identifiant de connexion fiable et unique en base.
 */
class PhoneNumber
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (strlen($digits) > 10 && str_starts_with($digits, '225')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }
}
