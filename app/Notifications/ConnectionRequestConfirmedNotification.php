<?php

namespace App\Notifications;

use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Phase 9 (§24) — in-app via ce canal ; l'e-mail associé (fort enjeu) est envoyé
 * séparément par App\Listeners\SendCollaborationConfirmedMail (classe Mail dédiée).
 */
class ConnectionRequestConfirmedNotification extends Notification
{
    public function __construct(public ConnectionRequest $connectionRequest, public User $actor) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'connection_request_confirmed',
            'title' => 'Collaboration confirmée',
            'message' => sprintf(
                '%s a confirmé la collaboration pour « %s ».',
                $this->actor->name,
                $this->connectionRequest->productLabel(),
            ),
            'url' => route('learner.requests.show', $this->connectionRequest),
        ];
    }
}
