<?php

namespace App\Policies;

use App\Enums\DeliveryAssistStatus;
use App\Models\CropOrder;
use App\Models\User;

/**
 * `view` reste couvert par le contournement admin habituel (consultation). Toutes les
 * actions qui font agir une PARTIE (accept/refuse/submitDeliveryConditions/
 * proposeDeliveryFee/acceptDeliveryFee/cancel/requestDeliveryAssistance/
 * declareSelfArrangedDelivery/confirmSelfArrangedDelivery/cancelSelfArrangedDelivery) en
 * sont explicitement exclues (AppServiceProvider::boot()) — jamais l'admin à la place d'un
 * producteur/acheteur. `markDeliveryAssistStep`/`approveDeliveryConditions`/
 * `rejectDeliveryConditions` restent les seuls pouvoirs propres à l'admin.
 */
class CropOrderPolicy
{
    public function view(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->isParty($user);
    }

    public function accept(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canBeAcceptedBy($user);
    }

    public function refuse(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canBeRefusedBy($user);
    }

    public function cancel(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canBeCancelledBy($user);
    }

    public function submitDeliveryConditions(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canSubmitDeliveryConditionsBy($user);
    }

    public function approveDeliveryConditions(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canReviewDeliveryConditionsBy($user);
    }

    public function rejectDeliveryConditions(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canReviewDeliveryConditionsBy($user);
    }

    public function proposeDeliveryFee(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canProposeDeliveryFeeBy($user);
    }

    public function acceptDeliveryFee(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canAcceptDeliveryFeeBy($user);
    }

    public function requestDeliveryAssistance(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canRequestDeliveryAssistanceBy($user);
    }

    public function markDeliveryAssistStep(User $user, CropOrder $cropOrder, DeliveryAssistStatus $to): bool
    {
        return $cropOrder->canMarkDeliveryAssistStepBy($user, $to);
    }

    public function declareSelfArrangedDelivery(User $user, CropOrder $cropOrder, string $mode): bool
    {
        return $cropOrder->canDeclareSelfArrangedDeliveryBy($user, $mode);
    }

    public function confirmSelfArrangedDelivery(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canConfirmSelfArrangedDeliveryBy($user);
    }

    public function cancelSelfArrangedDelivery(User $user, CropOrder $cropOrder): bool
    {
        return $cropOrder->canCancelSelfArrangedDeliveryBy($user);
    }
}
