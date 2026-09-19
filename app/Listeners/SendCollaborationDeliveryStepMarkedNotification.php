<?php

namespace App\Listeners;

use App\Events\CollaborationDeliveryStepMarked;
use App\Notifications\CollaborationDeliveryStepMarkedNotification;

/**
 * Phase 9 (§24) — « étape de livraison franchie » : in-app uniquement. markDeliveryStep()
 * peut être franchi par le producteur ou l'acheteur selon l'étape, on notifie celui qui
 * n'a pas agi (cf. Collaboration::otherParty).
 */
class SendCollaborationDeliveryStepMarkedNotification
{
    public function handle(CollaborationDeliveryStepMarked $event): void
    {
        $recipient = $event->collaboration->otherParty($event->actor);

        $recipient->notify(new CollaborationDeliveryStepMarkedNotification($event->collaboration, $event->actor));
    }
}
