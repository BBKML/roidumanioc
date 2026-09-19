<?php

namespace App\Policies;

use App\Models\ConnectionRequest;
use App\Models\User;

/**
 * Un administrateur passe tout (voir Gate::before dans AppServiceProvider). Chaque
 * capacité déléguée au prédicat `canXxxBy()` correspondant sur le modèle, pour que la
 * policy (couche UI) et la méthode de transition (garde-fou en profondeur) ne divergent
 * jamais.
 */
class ConnectionRequestPolicy
{
    public function view(User $user, ConnectionRequest $connectionRequest): bool
    {
        return $connectionRequest->isParty($user);
    }

    public function accept(User $user, ConnectionRequest $connectionRequest): bool
    {
        return $connectionRequest->canBeAcceptedBy($user);
    }

    public function cancel(User $user, ConnectionRequest $connectionRequest): bool
    {
        return $connectionRequest->canBeCancelledBy($user);
    }

    public function refuse(User $user, ConnectionRequest $connectionRequest): bool
    {
        return $connectionRequest->canBeRefusedBy($user);
    }

    public function moveToNegotiation(User $user, ConnectionRequest $connectionRequest): bool
    {
        return $connectionRequest->canMoveToNegotiationBy($user);
    }

    public function propose(User $user, ConnectionRequest $connectionRequest): bool
    {
        return $connectionRequest->canProposeBy($user);
    }

    public function confirmCollaboration(User $user, ConnectionRequest $connectionRequest): bool
    {
        return $connectionRequest->canConfirmCollaborationBy($user);
    }
}
