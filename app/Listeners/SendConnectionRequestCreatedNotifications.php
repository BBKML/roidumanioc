<?php

namespace App\Listeners;

use App\Events\ConnectionRequestCreated;
use App\Mail\NewConnectionRequestMail;
use App\Notifications\NewConnectionRequestNotification;
use Illuminate\Support\Facades\Mail;

/**
 * Phase 9 (§24) — « nouvelle demande » : in-app systématique + e-mail (fort enjeu),
 * jamais bloquant si le SMTP tombe (try/catch, comme partout ailleurs dans le projet).
 * Auto-découvert par Laravel (pas d'EventServiceProvider dans ce projet).
 */
class SendConnectionRequestCreatedNotifications
{
    public function handle(ConnectionRequestCreated $event): void
    {
        $recipient = $event->connectionRequest->receiverUser();

        $recipient->notify(new NewConnectionRequestNotification($event->connectionRequest));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new NewConnectionRequestMail($event->connectionRequest));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
