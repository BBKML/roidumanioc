<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/**
 * `view` reste couvert par le contournement admin habituel (Gate::before — lecture seule
 * pour modération, cf. cahier des charges §16). `send` en est explicitement exclu
 * (AppServiceProvider::boot()) : un administrateur ne doit jamais pouvoir écrire dans une
 * conversation à la place d'une des deux parties, seule la vraie appartenance compte ici.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->connectionRequest->isParty($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->connectionRequest->isParty($user);
    }
}
