<?php

namespace App\Livewire\Admin;

use App\Models\Collaboration;
use App\Models\CommunityPost;
use App\Models\ContactMessage;
use App\Models\Conversation;
use App\Models\CropOrder;
use App\Models\DeliveryAssist;
use App\Models\MarketplaceListing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProducerProfile;
use App\Models\RegistrationLead;
use Livewire\Component;

/**
 * Navigation latérale du back-office — compteurs rafraîchis en quasi-temps réel
 * (wire:poll), sans websocket.
 */
class Nav extends Component
{
    public function render()
    {
        $badges = [
            'admin.payments' => Payment::where('status', 'a_verifier')->count(),
            'admin.orders' => Order::where('status', 'validee')->count(),
            'admin.messages' => ContactMessage::where('status', 'nouveau')->count(),
            'admin.marketplace' => MarketplaceListing::where('status', 'en_attente')->count(),
            'admin.community' => CommunityPost::where('status', 'signale')->count(),
            'admin.registration-leads' => RegistrationLead::where('status', 'nouveau')->count(),
            'admin.conversations' => Conversation::query()
                ->whereHas('messages', fn ($q) => $q->where('contains_flagged_content', true), '>=', Conversation::FLAG_THRESHOLD)
                ->count(),
            'admin.producers' => ProducerProfile::whereNull('verified_at')->count(),
            'admin.collaborations' => Collaboration::where('status', 'litige')->count(),
            'admin.crop-orders' => CropOrder::whereIn('status', ['en_attente_producteur', 'en_attente_validation_admin'])->count(),
            'admin.delivery-assists' => DeliveryAssist::whereNotIn('status', ['livree', 'annulee'])->count(),
        ];

        $nav = [
            'Pilotage' => [
                ['admin.dashboard', "Vue d'ensemble", 'grid'],
                ['admin.payments', 'Paiements à vérifier', 'wallet'],
                ['admin.orders', 'Commandes', 'cart'],
                ['admin.messages', 'Messages reçus', 'chat'],
                ['admin.conversations', 'Conversations signalées', 'flag'],
            ],
            'Contenu' => [
                ['admin.content', 'Contenu du site', 'layout'],
                ['admin.formations', 'Formations', 'book'],
                ['admin.marketplace', 'Marketplace', 'spark'],
                ['admin.shop', 'Boutique officielle', 'box'],
                ['admin.events', 'Événements', 'calendar'],
                ['admin.community', 'Communauté', 'chat'],
            ],
            'Campagnes' => [
                ['admin.registration-forms', "Formulaires d'inscription", 'link'],
                ['admin.registration-leads', 'Prospects', 'users'],
            ],
            'Mise en relation' => [
                // Plus de "Chiffres de la mise en relation" ici : ces agrégats vivent
                // désormais dans le tableau de bord unique (admin.dashboard, Phase 16) —
                // sinon deux écrans candidats au titre de "vue d'ensemble" dans le même
                // menu, exactement le défaut corrigé côté apprenant en Phase 15.
                ['admin.producers', 'Producteurs', 'award'],
                ['admin.buyers', 'Acheteurs', 'basket'],
                ['admin.crop-offers', 'Offres', 'box'],
                ['admin.buyer-needs', 'Besoins', 'target'],
                ['admin.connection-requests', 'Demandes', 'link'],
                ['admin.collaborations', 'Collaborations', 'leaf'],
                ['admin.reviews', 'Avis', 'flag'],
                ['admin.crop-orders', 'Commandes producteur', 'cart'],
                ['admin.delivery-assists', 'Aide livraison', 'truck'],
            ],
            'Gestion' => [
                ['admin.members', 'Membres', 'users'],
                ['admin.newsletter', 'Infolettre', 'spark'],
                ['admin.activity', "Journal d'activité", 'grid'],
                ['admin.settings', 'Paramètres', 'gear'],
                ['account.edit', 'Mon compte', 'users'],
            ],
        ];

        return view('livewire.admin.nav', [
            'nav' => $nav,
            'badges' => $badges,
            'current' => request()->route()?->getName(),
        ]);
    }
}
