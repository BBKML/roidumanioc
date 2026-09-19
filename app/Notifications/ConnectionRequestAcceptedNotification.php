<?php

namespace App\Notifications;

use App\Models\ConnectionRequest;
use Illuminate\Notifications\Notification;

/** Phase 9 (§24) — in-app uniquement, pas dans la liste des événements à fort enjeu. */
class ConnectionRequestAcceptedNotification extends Notification
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
            'type' => 'connection_request_accepted',
            'title' => 'Demande acceptée',
            'message' => sprintf(
                'Votre demande pour « %s » a été acceptée.',
                $this->connectionRequest->productLabel(),
            ),
            'url' => route('learner.requests.show', $this->connectionRequest),
        ];
    }
}
