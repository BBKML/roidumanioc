<?php

namespace App\Notifications;

use App\Enums\CollaborationDeliveryStatus;
use App\Models\Collaboration;
use App\Models\User;
use Illuminate\Notifications\Notification;

/** Phase 9 (§24) — in-app uniquement, pas dans la liste des événements à fort enjeu. */
class CollaborationDeliveryStepMarkedNotification extends Notification
{
    public function __construct(public Collaboration $collaboration, public User $actor) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $step = $this->collaboration->delivery?->status ?? CollaborationDeliveryStatus::Prevue;

        return [
            'type' => 'collaboration_delivery_step_marked',
            'title' => 'Étape de livraison franchie',
            'message' => sprintf(
                "La livraison de « %s » est passée à l'étape « %s ».",
                $this->collaboration->agreed_product,
                $step->label(),
            ),
            'url' => route('learner.collaborations.show', $this->collaboration),
        ];
    }
}
