<?php

namespace App\Support;

/**
 * Détecte les tentatives d'échange de coordonnées personnelles dans un message de
 * conversation (cahier des charges §16 : « fonctionnalité stratégique »).
 *
 * Aide au tri, comme Payment::runAutoCheck() — de simples heuristiques (regex), jamais
 * une garantie à 100 % (§16 « Important ») : ne bloque jamais l'envoi, se contente de
 * détecter puis de masquer à l'affichage (voir ConversationMessage::displayBody()).
 * Aucune détection par IA/NLP ici, volontairement (hors périmètre).
 */
class ContactDetector
{
    private const MASK = '[coordonnée masquée]';

    /** Tournures françaises fréquentes pour amorcer un échange de coordonnées hors plateforme. */
    private const PHRASES = [
        'appelle[- ]moi',
        'appelez[- ]moi',
        'contacte[- ]moi',
        'contactez[- ]moi',
        'ecris[- ]moi',
        'écris[- ]moi',
        'mon (?:numero|numéro)',
        'mon whatsapp',
        'sur whatsapp',
        'ajoute[- ]moi',
        'trouve[- ]moi sur',
        'en dehors de la plateforme',
        'hors plateforme',
    ];

    /**
     * Passe le texte au crible de chaque heuristique.
     *
     * @return array<string, array<int, string>> vide si rien détecté, sinon
     *                                           [type => [extraits trouvés]] — type ∈ telephone|email|lien_whatsapp|
     *                                           lien_reseau_social|formule_contact.
     */
    public static function scan(string $text): array
    {
        $matches = array_filter([
            'telephone' => self::findPhoneNumbers($text),
            'email' => self::findEmails($text),
            'lien_whatsapp' => self::findMatches($text, '/(?:https?:\/\/)?(?:www\.)?(?:wa\.me|api\.whatsapp\.com|whatsapp\.com)\/\S+/i'),
            'lien_reseau_social' => self::findMatches($text, '/(?:https?:\/\/)?(?:www\.)?(?:facebook\.com|fb\.me|instagram\.com)\/\S+/i'),
            'formule_contact' => self::findMatches($text, '/(?:'.implode('|', self::PHRASES).')/iu'),
        ]);

        return $matches;
    }

    /** Vrai si au moins une heuristique s'est déclenchée. */
    public static function isFlagged(string $text): bool
    {
        return self::scan($text) !== [];
    }

    /** Remplace chaque extrait détecté par un marqueur neutre — pour l'affichage, jamais pour le stockage. */
    public static function mask(string $text, array $matches): string
    {
        // 'formule_contact' ne capture pas de donnée en soi (juste une tournure suspecte,
        // ex. « contactez-moi ») — rien à y masquer, seules les catégories qui contiennent
        // une véritable coordonnée sont remplacées.
        $maskable = array_diff_key($matches, ['formule_contact' => null]);
        $needles = array_unique(array_merge(...array_values($maskable ?: [[]])));

        // Les plus longs d'abord : évite qu'un extrait plus court masque une partie
        // seulement d'un extrait plus long qui le contient.
        usort($needles, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return $needles === [] ? $text : str_replace($needles, self::MASK, $text);
    }

    /**
     * Suite de chiffres avec séparateurs tolérés (espaces/points/tirets), au moins 8
     * chiffres une fois nettoyée — couvre les formats locaux (10 chiffres) et
     * internationaux (+225/00225… — jusqu'à 5 chiffres de préfixe + 10 chiffres locaux)
     * quelle que soit la ponctuation utilisée, sans avoir à énumérer chaque format
     * possible dans une regex géante.
     */
    private static function findPhoneNumbers(string $text): array
    {
        preg_match_all('/(?:\+?\d[\d .\-]{6,}\d)/', $text, $m);

        $found = [];
        foreach ($m[0] as $candidate) {
            $digits = preg_replace('/\D+/', '', $candidate);
            if (strlen($digits) >= 8 && strlen($digits) <= 15) {
                $found[] = trim($candidate);
            }
        }

        return array_values(array_unique($found));
    }

    private static function findEmails(string $text): array
    {
        return self::findMatches($text, '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i');
    }

    private static function findMatches(string $text, string $pattern): array
    {
        preg_match_all($pattern, $text, $m);

        return array_values(array_unique($m[0]));
    }
}
