<?php

namespace App\Listeners;

use App\Events\ConnectionRequestProposed;
use App\Notifications\ConnectionRequestProposedNotification;

/**
 * Phase 9 (§24) — « proposition reçue » : in-app uniquement. propose() est accessible
 * aux deux parties, on notifie celle qui n'a pas agi (cf. ConnectionRequest::otherParty).
 */
class SendConnectionRequestProposedNotification
{
    public function handle(ConnectionRequestProposed $event): void
    {
        $recipient = $event->connectionRequest->otherParty($event->actor);

        $recipient->notify(new ConnectionRequestProposedNotification($event->connectionRequest, $event->actor));
    }
}
