<?php

namespace App\Livewire\Connect;

use App\Actions\SendConnectionProposal;
use App\Actions\SendConversationMessage;
use App\Enums\CropUnit;
use App\Models\ConnectionRequest;
use App\Models\Conversation as ConversationModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Messagerie interne d'une demande — bulles classiques, actualisée par polling
 * (`wire:poll.5s`, pas de websocket dans ce projet). Intégrée en plein cadre sous
 * App\Livewire\Connect\Show (Phase 14).
 *
 * Porte aussi désormais la négociation elle-même (façon WhatsApp, cf. cahier des
 * charges) : « Faire une proposition » ouvre un petit formulaire structuré (quantité/
 * unité/prix) qui envoie une carte dédiée dans le fil, sur laquelle l'autre partie peut
 * Accepter/Refuser directement — mêmes transitions `propose()`/`confirmCollaboration()`/
 * `refuse()` que le panneau "Progression" du parent, jamais de logique dupliquée (les
 * prédicats/transitions restent sur le modèle ConnectionRequest).
 */
class Conversation extends Component
{
    public ConnectionRequest $connectionRequest;

    public string $body = '';

    public bool $showProposalForm = false;

    public string $proposalQuantity = '';

    public string $proposalUnit = 'kg';

    public string $proposalPrice = '';

    public string $proposalNote = '';

    public function mount(ConnectionRequest $connectionRequest): void
    {
        $this->connectionRequest = $connectionRequest;
    }

    public function send(): void
    {
        $this->validate(['body' => ['required', 'string', 'max:'.SendConversationMessage::MAX_LENGTH]]);

        try {
            app(SendConversationMessage::class)->handle(Auth::user(), $this->connectionRequest, $this->body);
        } catch (ValidationException $e) {
            $this->addError('body', $e->validator->errors()->first());

            return;
        }

        $this->reset('body');
    }

    public function startProposal(): void
    {
        $this->showProposalForm = true;
        $this->proposalQuantity = '';
        $this->proposalUnit = 'kg';
        $this->proposalPrice = '';
        $this->proposalNote = '';
        $this->resetErrorBag();
    }

    public function cancelProposal(): void
    {
        $this->showProposalForm = false;
        $this->resetErrorBag();
    }

    public function sendProposal(): void
    {
        try {
            app(SendConnectionProposal::class)->handle(
                Auth::user(),
                $this->connectionRequest,
                (float) str_replace(',', '.', $this->proposalQuantity),
                $this->proposalUnit,
                $this->proposalPrice !== '' ? (int) $this->proposalPrice : null,
                $this->proposalNote ?: null,
            );
        } catch (ValidationException $e) {
            // Correspondance explicite plutôt qu'une concaténation ('price_total' donnerait
            // 'proposalPrice_total', qui ne correspond à aucune propriété — bug détecté à
            // l'écriture des tests) : les clés de validation de SendConnectionProposal ne
            // portent pas les mêmes noms que les propriétés Livewire de ce composant.
            $fieldMap = [
                'quantity' => 'proposalQuantity',
                'unit' => 'proposalUnit',
                'price_total' => 'proposalPrice',
                'note' => 'proposalNote',
                'general' => 'proposalQuantity',
            ];

            foreach ($e->errors() as $field => $messages) {
                $this->addError($fieldMap[$field] ?? 'proposalQuantity', $messages[0]);
            }

            return;
        }

        $this->showProposalForm = false;
        $this->connectionRequest->refresh();
        $this->dispatch('notify', message: 'Proposition envoyée.');
        $this->dispatch('connection-request-updated');
    }

    public function acceptProposal(): void
    {
        $this->authorize('confirmCollaboration', $this->connectionRequest);

        if ($this->connectionRequest->confirmCollaboration(Auth::user())) {
            $this->dispatch('notify', message: 'Collaboration confirmée !');
        }

        $this->connectionRequest->refresh();
        $this->dispatch('connection-request-updated');
    }

    public function refuseProposal(): void
    {
        $this->authorize('refuse', $this->connectionRequest);

        if ($this->connectionRequest->refuse(Auth::user())) {
            $this->dispatch('notify', message: 'Proposition refusée — la demande est close.');
        }

        $this->connectionRequest->refresh();
        $this->dispatch('connection-request-updated');
    }

    public function render()
    {
        $user = Auth::user();
        $cr = $this->connectionRequest;
        $canView = $cr->isParty($user) || $user->isAdmin();

        // Requête directe plutôt que $this->connectionRequest->conversation : cette
        // relation, une fois accédée (et mise en cache à null) lors du tout premier
        // rendu — avant l'envoi du premier message — resterait figée à null sur
        // l'instance du modèle pour le reste du cycle de vie du composant Livewire,
        // même après la création réelle de la conversation par send().
        //
        // $canView (pas seulement `canSend`, cf. Sécurité) : ce composant n'est monté
        // qu'en enfant de Connect\Show (déjà autorisé), mais mount() ne le revérifie pas
        // — un tiers authentifié qui l'atteindrait quand même (composant jamais routé
        // directement aujourd'hui, mais pas de garantie future) ne doit jamais voir le
        // contenu des messages, même masqué — audit sécurité V1.
        $messages = $canView
            ? ConversationModel::where('connection_request_id', $cr->id)
                ->first()
                ?->messages()->with('sender')->oldest()->get() ?? collect()
            : collect();

        return view('livewire.connect.conversation', [
            'messages' => $messages,
            'canSend' => $cr->isParty($user),
            // Un admin qui modère doit voir le texte réel (traçabilité) — le masquage ne
            // protège que les deux parties l'une de l'autre, pas l'admin de sa propre modération.
            'isModerating' => $user->isAdmin() && ! $cr->isParty($user),
            'canPropose' => $cr->canProposeBy($user),
            'canConfirmCollaboration' => $cr->canConfirmCollaborationBy($user),
            'canRefuse' => $cr->canBeRefusedBy($user),
            // Une seule proposition peut jamais être "active" à la fois : la machine à
            // états ne permet pas de reproposer depuis `proposition` (seuls confirm/refuse
            // en sortent) — donc le dernier message de type proposition est nécessairement
            // LE sujet en cours tant que le statut est encore `proposition`.
            'latestProposalId' => $messages->where('type', 'proposition')->last()?->id,
            'requestStatus' => $cr->status->value,
            'units' => CropUnit::cases(),
        ]);
    }
}
