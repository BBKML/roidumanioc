<?php

namespace App\Notifications;

use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

/** Phase 9 (§24) — in-app uniquement, pas dans la liste des événements à fort enjeu. */
class ConnectionRequestProposedNotification extends Notification
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
            'type' => 'connection_request_proposed',
            'title' => 'Nouvelle proposition',
            'message' => sprintf(
                '%s a envoyé une proposition pour « %s ».',
                $this->actor->name,
                $this->connectionRequest->productLabel(),
            ),
            'url' => route('learner.requests.show', $this->connectionRequest),
        ];
    }
}
