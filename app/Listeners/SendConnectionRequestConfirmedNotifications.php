<?php

namespace App\Listeners;

use App\Events\ConnectionRequestConfirmed;
use App\Mail\CollaborationConfirmedMail;
use App\Notifications\ConnectionRequestConfirmedNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Phase 9 (§24) — « collaboration confirmée » : in-app + e-mail (fort enjeu), jamais
 * bloquant. confirmCollaboration() est accessible aux deux parties, on notifie celle qui
 * n'a pas agi (cf. ConnectionRequest::otherParty).
 */
class SendConnectionRequestConfirmedNotifications
{
    public function handle(ConnectionRequestConfirmed $event): void
    {
        $recipient = $event->connectionRequest->otherParty($event->actor);

        $recipient->notify(new ConnectionRequestConfirmedNotification($event->connectionRequest, $event->actor));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new CollaborationConfirmedMail($event->connectionRequest));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
