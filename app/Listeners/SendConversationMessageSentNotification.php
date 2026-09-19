<?php

namespace App\Listeners;

use App\Events\ConversationMessageSent;
use App\Notifications\ConversationMessageSentNotification;

/**
 * Phase 9 (§24) — « nouveau message » : in-app UNIQUEMENT, jamais d'e-mail (pour ne pas
 * spammer une messagerie potentiellement active — §24). Notifie l'autre partie de la
 * demande de mise en relation (jamais l'expéditeur lui-même).
 */
class SendConversationMessageSentNotification
{
    public function handle(ConversationMessageSent $event): void
    {
        $connectionRequest = $event->message->conversation->connectionRequest;
        $recipient = $connectionRequest->otherParty($event->message->sender);

        $recipient->notify(new ConversationMessageSentNotification($event->message));
    }
}
