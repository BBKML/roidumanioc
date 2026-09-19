<?php

namespace App\Livewire\Connect;

use App\Actions\SendConnectionProposal;
use App\Enums\ConnectionRequestStatus;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * L'écran le plus important de la mise en relation : stepper vertical du cycle de
 * statuts + actions disponibles pour l'utilisateur courant + historique.
 */
#[Layout('components.layouts.learner')]
class Show extends Component
{
    public ConnectionRequest $connectionRequest;

    public function mount(ConnectionRequest $connectionRequest): void
    {
        $this->authorize('view', $connectionRequest);

        $this->connectionRequest = $connectionRequest;
    }

    private function applyTransition(string $ability, string $method, string $message): void
    {
        $this->authorize($ability, $this->connectionRequest);

        if ($this->connectionRequest->{$method}(Auth::user())) {
            $this->dispatch('notify', message: $message);
        }

        $this->connectionRequest->refresh();
    }

    /**
     * Accepter puis passer en négociation faisaient auparavant deux clics consécutifs qui,
     * en pratique, arrivaient toujours l'un après l'autre — fusionnés en une seule action
     * (audit UX, retour utilisateur). Le modèle n'est PAS modifié : accept()/
     * moveToNegotiation() restent deux transitions distinctes, chacune journalisée
     * séparément (la traçabilité ne change pas, seul le nombre de clics change) — c'est
     * uniquement cet orchestrateur Livewire qui les enchaîne.
     *
     * Si le demandeur avait indiqué sa quantité/son prix dès le premier contact
     * (`initial_proposal_terms`, capturé par Connect\RequestOffer/RequestNeed), on va
     * jusqu'au bout en une seule action : la transformer en vraie proposition dans le chat
     * au nom du demandeur (`SendConnectionProposal` autorise via `Gate::forUser()`,
     * indépendant de qui a cliqué ici), PUIS confirmer directement la collaboration au nom
     * de celui qui vient d'accepter — cliquer « Accepter » quand les termes étaient déjà sur
     * la table VEUT DIRE qu'on est d'accord avec, pas seulement qu'on veut en discuter
     * (retour utilisateur explicite : « si le client est d'accord, il accepte et on passe à
     * l'étape fin »). `confirmCollaboration()` reste gardé par `canConfirmCollaborationBy()`
     * (jamais l'auteur de la proposition qui se confirme lui-même) : ici l'acceptant n'est
     * jamais l'auteur de sa PROPRE proposition — c'est justement l'autre partie qui avait
     * proposé — donc la garde passe normalement. Un échec à l'une ou l'autre étape (rate
     * limit épuisé, cas limite) laisse la demande à l'étape la plus avancée déjà atteinte,
     * jamais un échec bloquant sur l'acceptation elle-même.
     */
    public function accept(): void
    {
        $this->authorize('accept', $this->connectionRequest);

        if ($this->connectionRequest->accept(Auth::user())) {
            $this->connectionRequest->moveToNegotiation(Auth::user());

            $terms = $this->connectionRequest->initial_proposal_terms;
            $confirmed = false;

            if ($terms) {
                try {
                    app(SendConnectionProposal::class)->handle(
                        $this->connectionRequest->requester,
                        $this->connectionRequest,
                        (float) $terms['quantity'],
                        $terms['unit'],
                        $terms['price_total'] ?? null,
                        null,
                    );

                    $confirmed = $this->connectionRequest->confirmCollaboration(Auth::user());
                } catch (ValidationException) {
                    // Silencieux : la demande reste au dernier statut atteint, le demandeur
                    // pourra proposer/l'autre partie confirmer manuellement comme avant.
                }
            }

            $this->dispatch('notify', message: $confirmed
                ? 'Demande acceptée — collaboration confirmée !'
                : 'Demande acceptée — vous pouvez négocier.');
        }

        $this->connectionRequest->refresh();
    }

    public function cancel(): void
    {
        $this->applyTransition('cancel', 'cancel', 'Demande annulée.');
    }

    public function refuse(): void
    {
        $this->applyTransition('refuse', 'refuse', 'Demande refusée.');
    }

    /**
     * « Faire une proposition » se fait désormais depuis la messagerie (carte structurée,
     * Phase 14 — cf. Connect\Conversation::sendProposal()), pas depuis ce bouton générique
     * : plus de méthode `propose()` ici. `confirmCollaboration()`/`refuse()` restent au
     * même endroit qu'avant (accessibles à la fois depuis ce panneau ET depuis la carte de
     * proposition dans le chat — deux points d'entrée vers le même prédicat/transition,
     * jamais de logique dupliquée).
     */
    public function confirmCollaboration(): void
    {
        $this->authorize('confirmCollaboration', $this->connectionRequest);

        if ($this->connectionRequest->confirmCollaboration(Auth::user())) {
            $this->dispatch('notify', message: 'Collaboration confirmée !');
        }

        $this->connectionRequest->refresh();
    }

    /** Rafraîchit l'état après une action déclenchée depuis le composant enfant Conversation. */
    #[On('connection-request-updated')]
    public function refreshConnectionRequest(): void
    {
        $this->connectionRequest->refresh();
    }

    public function render()
    {
        $user = Auth::user();
        $cr = $this->connectionRequest;

        return view('livewire.connect.show', [
            'steps' => ConnectionRequestStatus::happyPath(),
            'furthestIndex' => $cr->furthestHappyPathIndex(),
            'currentIndex' => in_array($cr->status, ConnectionRequestStatus::happyPath(), true)
                ? array_search($cr->status, ConnectionRequestStatus::happyPath(), true)
                : null,
            'activities' => $cr->activities()->latest()->get(),
            'isRequester' => $cr->isRequester($user),
            'isReceiver' => $cr->isReceiver($user),
            'canAccept' => $cr->canBeAcceptedBy($user),
            'canCancel' => $cr->canBeCancelledBy($user),
            'canRefuse' => $cr->canBeRefusedBy($user),
            'canConfirmCollaboration' => $cr->canConfirmCollaborationBy($user),
            'collaboration' => Collaboration::where('connection_request_id', $cr->id)->first(),
            // Écran partagé : un admin peut l'ouvrir en lecture seule depuis
            // /admin/demandes (Phase 10, §37) sans être partie à la demande — jamais pour
            // agir à sa place (cf. Gate::before, AppServiceProvider). Affiché à part pour
            // que la vue le signale clairement, plutôt que de laisser croire qu'il navigue
            // comme n'importe quel apprenant (audit UX, Phase 13).
            'isViewerAdmin' => $user->isAdmin() && ! $cr->isParty($user),
        ]);
    }
}
