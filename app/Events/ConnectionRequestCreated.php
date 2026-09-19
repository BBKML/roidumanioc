<?php

namespace App\Events;

use App\Models\ConnectionRequest;

/**
 * Point d'extension pour les notifications (Phase 9) — aucun listener pour l'instant.
 * Dispatché par App\Actions\CreateConnectionRequest (la partie qui reçoit la demande
 * est celle qui doit être notifiée).
 */
class ConnectionRequestCreated
{
    public function __construct(public ConnectionRequest $connectionRequest) {}
}
