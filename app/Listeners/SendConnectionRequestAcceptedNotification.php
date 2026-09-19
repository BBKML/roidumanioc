<?php

namespace App\Listeners;

use App\Events\ConnectionRequestAccepted;
use App\Notifications\ConnectionRequestAcceptedNotification;

/** Phase 9 (§24) — « demande acceptée » : in-app uniquement. Notifie le demandeur. */
class SendConnectionRequestAcceptedNotification
{
    public function handle(ConnectionRequestAccepted $event): void
    {
        $event->connectionRequest->requester->notify(
            new ConnectionRequestAcceptedNotification($event->connectionRequest)
        );
    }
}
