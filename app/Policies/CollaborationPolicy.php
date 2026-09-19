<?php

namespace App\Policies;

use App\Enums\CollaborationDeliveryStatus;
use App\Models\Collaboration;
use App\Models\User;

/**
 * `view` reste couvert par le contournement admin habituel (consultation, §18/§19).
 * Toutes les actions qui font agir une PARTIE (declarePayment/confirmPayment/
 * contestPayment/markDeliveryStep/cancel) en sont explicitement exclues
 * (AppServiceProvider::boot()) — jamais l'admin à la place d'un producteur/acheteur.
 * `markDisputed` reste le seul pouvoir propre à l'admin.
 */
class CollaborationPolicy
{
    public function view(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->isParty($user);
    }

    public function declarePayment(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->canDeclarePaymentBy($user);
    }

    public function confirmPayment(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->canRespondToPaymentBy($user);
    }

    public function contestPayment(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->canRespondToPaymentBy($user);
    }

    public function markDeliveryStep(User $user, Collaboration $collaboration, CollaborationDeliveryStatus $to): bool
    {
        return $collaboration->canMarkDeliveryStepBy($user, $to);
    }

    public function cancel(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->canBeCancelledBy($user);
    }

    public function markDisputed(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->canBeDisputedBy($user);
    }

    public function resolveDispute(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->canResolveDisputeBy($user);
    }
}
