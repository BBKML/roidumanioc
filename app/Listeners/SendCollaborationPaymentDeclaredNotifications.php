<?php

namespace App\Listeners;

use App\Events\CollaborationPaymentDeclared;
use App\Mail\CollaborationPaymentDeclaredMail;
use App\Notifications\CollaborationPaymentDeclaredNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Phase 9 (§24) — « paiement déclaré » : in-app + e-mail (fort enjeu), jamais bloquant.
 * Toujours l'acheteur qui déclare, toujours le producteur qui est notifié.
 */
class SendCollaborationPaymentDeclaredNotifications
{
    public function handle(CollaborationPaymentDeclared $event): void
    {
        $recipient = $event->collaboration->producerProfile->user;

        $recipient->notify(new CollaborationPaymentDeclaredNotification($event->collaboration));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new CollaborationPaymentDeclaredMail($event->collaboration));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
