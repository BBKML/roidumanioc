<?php

namespace App\Events;

use App\Models\Collaboration;
use App\Models\User;

/**
 * Complète le point d'extension laissé par la Phase 7 — branché à la Phase 9. Porte
 * `$actor` (contrairement aux autres events de Collaboration) : cette étape peut être
 * franchie par le producteur (en_cours/effectuee) OU l'acheteur (receptionnee), il faut
 * donc savoir qui a agi pour notifier l'AUTRE partie, pas l'auteur de l'action.
 */
class CollaborationDeliveryStepMarked
{
    public function __construct(public Collaboration $collaboration, public User $actor) {}
}
