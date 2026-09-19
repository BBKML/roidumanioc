<?php

namespace App\Notifications;

use App\Models\Collaboration;
use Illuminate\Notifications\Notification;

/**
 * Phase 9 (§24) — in-app via ce canal ; l'e-mail associé (fort enjeu) est envoyé
 * séparément par App\Listeners\SendPaymentDeclaredMail (classe Mail dédiée). Toujours
 * l'acheteur qui déclare, toujours le producteur qui est notifié (cf.
 * Collaboration::canDeclarePaymentBy).
 */
class CollaborationPaymentDeclaredNotification extends Notification
{
    public function __construct(public Collaboration $collaboration) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'collaboration_payment_declared',
            'title' => 'Paiement déclaré',
            'message' => sprintf(
                "L'acheteur a déclaré un paiement pour « %s ». Vérifiez et confirmez la réception.",
                $this->collaboration->agreed_product,
            ),
            'url' => route('learner.collaborations.show', $this->collaboration),
        ];
    }
}
