<?php

namespace App\Policies;

use App\Models\Formation;
use App\Models\User;

/**
 * Un administrateur passe tout (voir Gate::before dans AppServiceProvider).
 * Ces règles concernent donc les apprenants.
 */
class FormationPolicy
{
    /**
     * Suivre la formation : voir les leçons, marquer la progression.
     * Autorisé si la formation est gratuite OU si l'apprenant a une inscription validée.
     */
    public function follow(User $user, Formation $formation): bool
    {
        if ($formation->isFree()) {
            return true;
        }

        return $user->isEnrolledIn($formation);
    }

    /**
     * S'inscrire (déclencher le parcours d'achat).
     */
    public function enroll(User $user, Formation $formation): bool
    {
        return $formation->status->value === 'publiee'
            && ! $user->isEnrolledIn($formation)
            && ! $user->hasPendingPaymentFor($formation);
    }
}
