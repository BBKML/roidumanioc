<?php

namespace App\Notifications;

use App\Models\ConnectionRequest;
use Illuminate\Notifications\Notification;

/**
 * Phase 9 (§24) — in-app uniquement via ce canal ; l'e-mail associé (fort enjeu) est
 * envoyé séparément par App\Listeners\SendNewConnectionRequestMail avec une classe Mail
 * dédiée, pas via le canal `mail` natif (cf. Backend & règles métier de la Phase 9).
 */
class NewConnectionRequestNotification extends Notification
{
    public function __construct(public ConnectionRequest $connectionRequest) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'connection_request_created',
            'title' => 'Nouvelle demande de mise en relation',
            'message' => sprintf(
                '%s souhaite être mis en relation avec vous pour « %s ».',
                $this->connectionRequest->requester->name,
                $this->connectionRequest->productLabel(),
            ),
            'url' => route('learner.requests.show', $this->connectionRequest),
        ];
    }
}
