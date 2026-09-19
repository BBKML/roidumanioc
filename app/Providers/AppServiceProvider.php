<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsBuyer;
use App\Http\Middleware\EnsureUserIsProducer;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\CropOrder;
use App\Models\Review;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Un administrateur actif passe toutes les autorisations — SAUF les actions qui font
        // agir une PARTIE à la place d'une autre : envoyer un message de conversation
        // (Conversation::send, §16), faire avancer une mise en relation (accepter/refuser/
        // négocier/proposer/confirmer — §14), toucher une collaboration pair-à-pair
        // (déclarer/confirmer/contester un paiement, avancer une livraison, annuler —
        // §18/§19), ou déposer un avis (Review::create, §21 — un admin n'est jamais partie
        // à une collaboration ni à une demande). L'admin y garde un accès lecture seule
        // (+ marquer/résoudre un litige, vérifier un producteur, ses seuls pouvoirs
        // propres) ; on laisse alors la policy concernée trancher normalement, ce qui
        // revient à exiger que l'utilisateur soit réellement une des deux parties.
        //
        // Ces deux listes (ConnectionRequest/Collaboration) sont redondantes avec le
        // garde-fou déjà présent DANS chaque méthode de transition (canXxxBy($actor) —
        // voir ConnectionRequest::accept() etc.), qui bloquerait de toute façon un admin
        // non-partie : elles existent pour que la couche POLICY elle-même ne mente jamais
        // (un `Gate::allows()` isolé, sans appeler la transition, doit répondre correctement
        // pour un admin non-partie — audit sécurité V1, cf. CLAUDE.md).
        //
        // `create` est le seul cas où $arguments[0] est un nom de classe (string) plutôt
        // qu'une instance : Livewire\Connect\ReviewForm appelle
        // authorize('create', [Review::class, $collaboration]), donc le sujet à comparer
        // est $arguments[1], pas $arguments[0] — voir Gate::raw()/callBeforeCallbacks().
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            $subject = $arguments[0] ?? null;

            if ($ability === 'send' && $subject instanceof Conversation) {
                return null;
            }

            $connectionRequestPartyOnly = ['accept', 'cancel', 'refuse', 'moveToNegotiation', 'propose', 'confirmCollaboration'];
            if (in_array($ability, $connectionRequestPartyOnly, true) && $subject instanceof ConnectionRequest) {
                return null;
            }

            $collaborationPartyOnly = ['declarePayment', 'confirmPayment', 'contestPayment', 'markDeliveryStep', 'cancel'];
            if (in_array($ability, $collaborationPartyOnly, true) && $subject instanceof Collaboration) {
                return null;
            }

            // Parcours de commande CropOrder (§14 bis, distinct du chat) — markDeliveryAssistStep
            // reste hors de cette liste : c'est le seul pouvoir propre de l'admin sur CropOrder,
            // symétrique de markDisputed() sur Collaboration.
            $cropOrderPartyOnly = [
                'accept', 'refuse', 'submitDeliveryConditions', 'proposeDeliveryFee', 'acceptDeliveryFee',
                'cancel', 'requestDeliveryAssistance', 'declareSelfArrangedDelivery',
                'confirmSelfArrangedDelivery', 'cancelSelfArrangedDelivery',
            ];
            if (in_array($ability, $cropOrderPartyOnly, true) && $subject instanceof CropOrder) {
                return null;
            }

            if ($ability === 'create' && $subject === Review::class) {
                return null;
            }

            if ($user->isAdmin() && $user->isActive()) {
                return true;
            }

            return null;
        });

        // Les requêtes Livewire (wire:click…) ne rejouent pas le middleware de route par défaut.
        // On force la re-vérification du rôle / du statut à chaque mise à jour de composant.
        // ThrottleRequests : re-applique le "throttle:6,1" des routes qui l'utilisent (ex. création
        // d'offre producteur) à chaque wire:click, pas seulement au chargement initial de la page.
        Livewire::addPersistentMiddleware([
            EnsureUserIsAdmin::class,
            EnsureUserIsActive::class,
            EnsureUserIsProducer::class,
            EnsureUserIsBuyer::class,
            ThrottleRequests::class,
        ]);

        // Politique de mot de passe : 8+ caractères, lettres et chiffres.
        // En production on refuse aussi les mots de passe connus des fuites (base HIBP).
        Password::defaults(fn () => Password::min(8)
            ->letters()
            ->numbers()
            ->when($this->app->isProduction(), fn (Password $rule) => $rule->uncompromised()));

        // Pagination du back-office : gabarit maison (le CSS admin n'est pas Tailwind).
        Paginator::defaultView('vendor.pagination.adm');
        Paginator::defaultSimpleView('vendor.pagination.adm');
    }
}
