<?php

namespace App\Notifications;

use App\Models\ConversationMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 9 (§24) — in-app UNIQUEMENT, jamais d'e-mail (§16 : ne pas sur-notifier une
 * messagerie potentiellement active). Le corps du message n'apparaît pas dans la
 * notification (juste l'expéditeur) — cohérent avec la Phase 6, la coordonnée éventuelle
 * y est de toute façon masquée à l'affichage, pas dans les métadonnées de notification.
 */
class ConversationMessageSentNotification extends Notification
{
    public function __construct(public ConversationMessage $message) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $connectionRequest = $this->message->conversation->connectionRequest;

        return [
            'type' => 'conversation_message_sent',
            'title' => 'Nouveau message',
            'message' => sprintf(
                '%s vous a envoyé un message à propos de « %s ».',
                $this->message->sender->name,
                $connectionRequest->productLabel(),
            ),
            'url' => route('learner.requests.show', $connectionRequest),
        ];
    }
}
