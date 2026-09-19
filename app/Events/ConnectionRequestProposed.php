<?php

namespace App\Events;

use App\Models\ConnectionRequest;
use App\Models\User;

/**
 * Branché à la Phase 9. Porte `$actor` : propose() est accessible aux deux parties, il
 * faut donc savoir qui a proposé pour notifier l'AUTRE — contrairement à
 * ConnectionRequestAccepted/Created, toujours dans un seul sens (garde asymétrique).
 */
class ConnectionRequestProposed
{
    public function __construct(public ConnectionRequest $connectionRequest, public User $actor) {}
}
