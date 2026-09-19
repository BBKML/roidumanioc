<?php

namespace App\Events;

use App\Models\ConnectionRequest;

/**
 * Point d'extension pour les notifications (Phase 9) — aucun listener pour l'instant.
 */
class ConnectionRequestRefused
{
    public function __construct(public ConnectionRequest $connectionRequest) {}
}
