<?php

namespace App\Livewire\Learner;

use App\Enums\BuyerNeedStatus;
use App\Enums\CollaborationStatus;
use App\Enums\ConnectionRequestStatus;
use App\Enums\CropOfferStatus;
use App\Enums\CropOrderStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\Event;
use App\Models\User;
use App\Notifications\ConversationMessageSentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tableau de bord UNIQUE de l'espace apprenant (Phase 15 — audit UX : un compte cumulant
 * producteur ET/OU acheteur voyait jusque-là un lien de menu distinct par espace, dont
 * DEUX portant le même libellé « Tableau de bord » — confusion signalée en usage réel).
 * Cette page réunit désormais tout ce qui s'applique au compte connecté : progression des
 * formations (toujours), + section « Espace producteur » (si `isProducer()`) et/ou
 * « Espace acheteur » (si `isBuyer()`) — jamais plusieurs pages de tableau de bord.
 * Remplace les anciens App\Livewire\Producer\Dashboard / Buyer\Dashboard (supprimés).
 */
#[Layout('components.layouts.learner')]
class Dashboard extends Component
{
    /**
     * Un message reçu (offre/besoin en négociation) doit se remarquer dès la connexion,
     * pas seulement dans la petite cloche du topbar — demande explicite : « le message
     * envoyé par acheteurs ou producteurs [doit] attirer l'attention une fois connecté
     * dans son tableau de bord ». Marque lu au clic, même mécanique que NotificationBell.
     */
    public function markMessageRead(string $notificationId): void
    {
        Auth::user()->notifications()->whereKey($notificationId)->first()?->markAsRead();
    }

    public function markAllMessagesRead(): void
    {
        Auth::user()->unreadNotifications()
            ->where('type', ConversationMessageSentNotification::class)
            ->update(['read_at' => now()]);
    }

    /**
     * Négociations/collaborations en cours pour une section du tableau de bord — sans ça,
     * l'utilisateur doit quitter le tableau de bord et fouiller dans « Mes demandes »
     * pour retrouver ce qu'il a en cours (audit UX : parcours pas assez guidé). Mélange
     * les deux types dans une seule liste (comme le ferait une page « Mes commandes »)
     * plutôt que deux listes séparées, triée par mise à jour la plus récente.
     */
    private function activeItems(User $viewer, iterable $requests, iterable $collaborations, \Closure $otherPartyForCollaboration): Collection
    {
        $requestItems = collect($requests)->map(fn (ConnectionRequest $cr) => [
            'status' => $cr->status,
            'label' => $cr->productLabel(),
            'otherParty' => $cr->otherParty($viewer)->name,
            'url' => route('learner.requests.show', $cr),
            'updatedAt' => $cr->updated_at,
        ]);

        $collaborationItems = collect($collaborations)->map(fn (Collaboration $c) => [
            'status' => $c->status,
            'label' => $c->agreed_product,
            'otherParty' => $otherPartyForCollaboration($c),
            'url' => route('learner.collaborations.show', $c),
            'updatedAt' => $c->updated_at,
        ]);

        return $requestItems->merge($collaborationItems)->sortByDesc('updatedAt')->take(5)->values();
    }

    /** Valeurs des statuts non terminaux de ConnectionRequest — pour filtrer les listes « en cours ». */
    private function activeConnectionRequestStatuses(): array
    {
        return array_map(
            fn ($s) => $s->value,
            array_filter(ConnectionRequestStatus::cases(), fn ($s) => ! $s->isTerminal()),
        );
    }

    public function render()
    {
        $user = Auth::user();

        $enrolled = $user->enrollments()
            ->where('status', EnrollmentStatus::Validee)
            ->with('formation.lessons')
            ->get();

        // Une seule requête pour toutes les leçons terminées (évite le N+1).
        $completedLessonIds = $user->lessonProgress()->pluck('lesson_id')->flip();

        $isProducer = $user->isProducer();
        $isBuyer = $user->isBuyer();

        $data = [
            'enrolled' => $enrolled,
            'completedLessonIds' => $completedLessonIds,
            'pending' => $user->enrollments()
                ->where('status', EnrollmentStatus::Paiement)
                ->with('formation')
                ->get(),
            'events' => Event::upcoming()->take(4)->get(),
            'isProducer' => $isProducer,
            'isBuyer' => $isBuyer,
        ];

        if ($isProducer) {
            $profile = $user->producerProfile;
            $collaborations = Collaboration::where('producer_profile_id', $profile->id)->get(['status']);

            $data += [
                'producerProfile' => $profile,
                'producerOffersPublishedCount' => $profile->cropOffers()->where('status', CropOfferStatus::Publiee)->count(),
                'producerOffersTotalCount' => $profile->cropOffers()->count(),
                'producerRequestsReceivedCount' => $profile->connectionRequests()->count(),
                'producerRequestsPendingCount' => $profile->connectionRequests()
                    ->where('status', ConnectionRequestStatus::EnAttente)->count(),
                'producerCollaborationsOngoing' => $collaborations
                    ->whereNotIn('status', [CollaborationStatus::Terminee, CollaborationStatus::Annulee, CollaborationStatus::Litige])
                    ->count(),
                'producerCollaborationsDone' => $collaborations->where('status', CollaborationStatus::Terminee)->count(),
                'producerAverageRating' => $profile->averageRating(),
                'producerReviewsCount' => $profile->reviewsCount(),
                'producerCropOrdersPendingCount' => $profile->cropOrders()->where('status', CropOrderStatus::EnAttenteProducteur)->count(),
                'producerCropOrdersActive' => $profile->cropOrders()
                    ->whereNotIn('status', [CropOrderStatus::Livree, CropOrderStatus::Refusee, CropOrderStatus::Annulee])
                    ->with('buyerProfile')
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(),
                'producerActiveItems' => $this->activeItems(
                    $user,
                    $profile->connectionRequests()->whereIn('status', $this->activeConnectionRequestStatuses())
                        ->latest('updated_at')->limit(5)->get(),
                    Collaboration::where('producer_profile_id', $profile->id)
                        ->whereNotIn('status', [CollaborationStatus::Terminee, CollaborationStatus::Annulee, CollaborationStatus::Litige])
                        ->latest('updated_at')->limit(5)->get(),
                    fn (Collaboration $c) => $c->buyerProfile->company_name ?: 'Acheteur',
                ),
            ];
        }

        if ($isBuyer) {
            $profile = $user->buyerProfile;
            $collaborations = Collaboration::where('buyer_profile_id', $profile->id)->get(['status']);

            $data += [
                'buyerProfile' => $profile,
                'buyerNeedsOpenCount' => $profile->buyerNeeds()->where('status', BuyerNeedStatus::Ouvert)->count(),
                'buyerNeedsTotalCount' => $profile->buyerNeeds()->count(),
                'buyerRequestsReceivedCount' => $profile->connectionRequests()->count(),
                'buyerCollaborationsOngoing' => $collaborations
                    ->whereNotIn('status', [CollaborationStatus::Terminee, CollaborationStatus::Annulee, CollaborationStatus::Litige])
                    ->count(),
                'buyerCollaborationsDone' => $collaborations->where('status', CollaborationStatus::Terminee)->count(),
                'buyerFavoritesCount' => $user->favoriteProducers()->count(),
                'buyerRecentOffers' => CropOffer::published()->with('producerProfile')->latest()->limit(3)->get(),
                'buyerCropOrdersActive' => $profile->cropOrders()
                    ->whereNotIn('status', [CropOrderStatus::Livree, CropOrderStatus::Refusee, CropOrderStatus::Annulee])
                    ->with('producerProfile')
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(),
                'buyerActiveItems' => $this->activeItems(
                    $user,
                    $profile->connectionRequests()->whereIn('status', $this->activeConnectionRequestStatuses())
                        ->latest('updated_at')->limit(5)->get(),
                    Collaboration::where('buyer_profile_id', $profile->id)
                        ->whereNotIn('status', [CollaborationStatus::Terminee, CollaborationStatus::Annulee, CollaborationStatus::Litige])
                        ->latest('updated_at')->limit(5)->get(),
                    fn (Collaboration $c) => $c->producerProfile->business_name,
                ),
            ];
        }

        if ($isProducer || $isBuyer) {
            $data['connectUnreadNotificationsCount'] = $user->unreadNotifications()->count();
            // Isolées du reste (demande acceptée, paiement déclaré, etc.) : ce sont les
            // seules à porter un aperçu du texte reçu, donc les seules qui méritent une
            // bannière dédiée plutôt qu'un simple compteur.
            $data['unreadMessages'] = $user->unreadNotifications()
                ->where('type', ConversationMessageSentNotification::class)
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('livewire.learner.dashboard', $data);
    }
}
