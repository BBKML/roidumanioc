<?php

namespace App\Actions;

use App\Enums\CropUnit;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Support\ContactDetector;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Envoie une proposition structurée (quantité/unité/prix + note facultative) depuis la
 * messagerie d'une demande de mise en relation (Phase 14 — négociation façon WhatsApp).
 * Point d'entrée unique, même famille que SendConversationMessage/CreateConnectionRequest :
 *
 * - l'autorisation réutilise l'ability `propose` existante (ConnectionRequestPolicy →
 *   ConnectionRequest::canProposeBy()) — pas de nouvelle règle à maintenir en double ;
 * - la transition de statut (`negociation` → `proposition`) est celle qui existe déjà sur
 *   le modèle, inchangée — cette action ne fait qu'y accoler un message enrichi ;
 * - la note facultative passe par ContactDetector comme n'importe quel message (§16) ;
 * - ne dispatch PAS ConversationMessageSent : ConnectionRequest::propose() déclenche déjà
 *   ConnectionRequestProposed, qui a sa propre notification (« proposition reçue », §24) —
 *   dispatcher les deux doublonnerait la notification pour la même action (sur-notifier,
 *   contraire à l'esprit du §24).
 */
class SendConnectionProposal
{
    public function handle(
        User $sender,
        ConnectionRequest $connectionRequest,
        float $quantity,
        string $unit,
        ?int $priceTotal,
        ?string $note,
    ): ConversationMessage {
        abort_unless($sender->isActive(), 403, 'Compte inactif.');

        $conversation = Conversation::firstOrCreate(['connection_request_id' => $connectionRequest->id]);

        // Réutilise l'ability existante plutôt qu'une règle propre à cette action — c'est
        // exactement la même autorisation que le bouton "Faire une proposition" d'origine.
        Gate::forUser($sender)->authorize('propose', $connectionRequest);

        $key = 'conversation-message:'.$sender->id;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            throw ValidationException::withMessages([
                'general' => 'Trop de messages envoyés. Patientez un instant.',
            ]);
        }
        RateLimiter::hit($key, 60);

        Validator::make(
            ['quantity' => $quantity, 'unit' => $unit, 'price_total' => $priceTotal, 'note' => $note],
            [
                'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
                'unit' => ['required', 'in:'.implode(',', array_column(CropUnit::cases(), 'value'))],
                'price_total' => ['nullable', 'integer', 'min:1'],
                'note' => ['nullable', 'string', 'max:'.SendConversationMessage::MAX_LENGTH],
            ],
        )->validate();

        // Transition inchangée (canProposeBy — statut négociation, l'une des deux parties) ;
        // si elle échoue (double-clic, statut déjà avancé entre-temps), on ne crée aucun
        // message fantôme qui prétendrait une proposition jamais réellement actée.
        if (! $connectionRequest->propose($sender)) {
            throw ValidationException::withMessages([
                'general' => 'Cette proposition n\'a pas pu être envoyée (statut de la demande déjà changé).',
            ]);
        }

        $note = $note !== null ? trim($note) : '';
        $matches = $note !== '' ? ContactDetector::scan($note) : [];

        return $conversation->messages()->create([
            'sender_id' => $sender->id,
            'type' => 'proposition',
            'body' => $note,
            'proposal_terms' => [
                'quantity' => $quantity,
                'unit' => $unit,
                'price_total' => $priceTotal,
            ],
            'contains_flagged_content' => $matches !== [],
            'flagged_patterns' => $matches !== [] ? $matches : null,
        ]);
    }
}
