<?php

namespace App\Events;

use App\Models\ConnectionRequest;
use App\Models\User;

/**
 * Branché à la Phase 9. Porte `$actor` — confirmCollaboration() est accessible aux deux
 * parties, il faut donc savoir qui a confirmé pour notifier l'AUTRE.
 */
class ConnectionRequestConfirmed
{
    public function __construct(public ConnectionRequest $connectionRequest, public User $actor) {}
}
