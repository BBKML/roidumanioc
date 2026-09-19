<?php

namespace App\Actions;

use App\Enums\ReviewDirection;
use App\Events\ReviewSubmitted;
use App\Models\Collaboration;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Point d'entrée unique pour déposer un avis — même esprit que DeclarePayment /
 * CreateConnectionRequest / SendConversationMessage. La direction (qui note qui) et le
 * destinataire sont déduits du rôle de l'auteur dans la collaboration, jamais reçus du
 * client.
 */
class SubmitReview
{
    /** @param  array<string, int>  $criteria  */
    public function handle(User $rater, Collaboration $collaboration, int $rating, array $criteria, ?string $comment = null): Review
    {
        abort_unless($rater->isActive(), 403, 'Compte inactif.');

        // Même famille que DeclarePayment/CreateConnectionRequest/SendConversationMessage
        // (audit sécurité V1) — la contrainte d'unicité (collaboration, auteur) limite déjà
        // fortement l'abus, mais ce garde-fou protège contre un martèlement de soumissions
        // invalides (mauvais critères, etc.) qui n'écrivent jamais en base.
        $key = 'submit-review:'.$rater->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'rating' => 'Trop de tentatives. Réessayez plus tard.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        Gate::forUser($rater)->authorize('create', [Review::class, $collaboration]);

        $direction = $collaboration->isProducer($rater)
            ? ReviewDirection::ProducteurVersAcheteur
            : ReviewDirection::AcheteurVersProducteur;

        $ratee = $collaboration->isProducer($rater)
            ? $collaboration->buyerProfile->user
            : $collaboration->producerProfile->user;

        // Ne garde que les clés attendues pour cette direction — un client ne peut pas
        // injecter une clé arbitraire dans le JSON stocké.
        $allowedKeys = array_keys($direction->criteria());
        $criteria = array_intersect_key($criteria, array_flip($allowedKeys));

        if (count($criteria) !== count($allowedKeys)) {
            throw ValidationException::withMessages([
                'criteria' => 'Merci de noter chaque critère.',
            ]);
        }

        $review = Review::create([
            'collaboration_id' => $collaboration->id,
            'rater_id' => $rater->id,
            'ratee_id' => $ratee->id,
            'direction' => $direction,
            'rating' => $rating,
            'criteria' => $criteria,
            'comment' => $comment,
        ]);

        event(new ReviewSubmitted($review));

        return $review;
    }
}
