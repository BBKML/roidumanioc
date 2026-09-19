<?php

namespace App\Actions;

use App\Events\ConversationMessageSent;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Support\ContactDetector;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Point d'entrée unique pour envoyer un message de conversation — même esprit que
 * App\Actions\DeclarePayment / CreateConnectionRequest.
 *
 * Sécurité :
 * - compte suspendu bloqué ;
 * - autorisation vérifiée via ConversationPolicy (jamais de bypass admin sur `send`,
 *   cf. AppServiceProvider::boot()) ;
 * - rate limit par utilisateur ;
 * - taille max ;
 * - détection (pas blocage) des tentatives d'échange de coordonnées — §16.
 */
class SendConversationMessage
{
    public const MAX_LENGTH = 2000;

    /**
     * `$notify = false` : réservé au message initial auto-posté par CreateConnectionRequest
     * (§24 — la création de la demande a déjà sa propre notification « nouvelle demande » ;
     * en émettre une seconde pour ce même message serait sur-notifier pour une seule action
     * de l'utilisateur). Tout appel normal (le formulaire de la messagerie) garde `true`.
     */
    public function handle(User $sender, ConnectionRequest $connectionRequest, string $body, bool $notify = true): ConversationMessage
    {
        abort_unless($sender->isActive(), 403, 'Compte inactif.');

        // Créée à la volée au premier message de la demande (pas avant).
        $conversation = Conversation::firstOrCreate(['connection_request_id' => $connectionRequest->id]);

        Gate::forUser($sender)->authorize('send', $conversation);

        $key = 'conversation-message:'.$sender->id;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            throw ValidationException::withMessages([
                'body' => 'Trop de messages envoyés. Patientez un instant.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $body = trim($body);
        if ($body === '' || mb_strlen($body) > self::MAX_LENGTH) {
            throw ValidationException::withMessages([
                'body' => 'Message invalide.',
            ]);
        }

        $matches = ContactDetector::scan($body);

        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
            'contains_flagged_content' => $matches !== [],
            'flagged_patterns' => $matches !== [] ? $matches : null,
        ]);

        if ($notify) {
            event(new ConversationMessageSent($message));
        }

        return $message;
    }
}
