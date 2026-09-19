<?php

namespace App\Livewire\Admin;

use App\Enums\CollaborationStatus;
use App\Enums\CropOrderStatus;
use App\Enums\OrderStatus;
use App\Models\BuyerNeed;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\ContactMessage;
use App\Models\CropOffer;
use App\Models\CropOrder;
use App\Models\DeliveryAssist;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\MarketplaceListing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tableau de bord UNIQUE de l'administration (Phase 16 — même principe que Phase 15 côté
 * apprenant : un compte ne doit jamais voir deux écrans candidats au titre de « vue
 * d'ensemble »). Absorbe l'ancien App\Livewire\Admin\ConnectStats (/admin/mise-en-relation,
 * supprimé) — ses agrégats sont désormais une section de plus sur cette même page, pas un
 * second tableau de bord.
 */
#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render()
    {
        $toVerify = Payment::toVerify()->with('user')->latest('submitted_at')->get();

        // Inscriptions validées, regroupées par mois sur les 5 derniers mois.
        $bars = collect(range(4, 0))->map(function (int $back) {
            $month = Carbon::now()->startOfMonth()->subMonths($back);

            return [
                'label' => ucfirst($month->translatedFormat('M')),
                'value' => Enrollment::where('status', 'validee')
                    ->whereBetween('enrolled_at', [$month, (clone $month)->endOfMonth()])
                    ->count(),
            ];
        });

        $revenueOnline = (int) Payment::where('status', 'confirme')->sum('amount');
        $revenueDelivery = (int) Order::where('status', OrderStatus::Livree)
            ->where('payment_mode', 'on_delivery')
            ->get()
            ->sum(fn (Order $o) => $o->total());

        $collaborations = Collaboration::with(['connectionRequest.cropOffer', 'connectionRequest.buyerNeed'])
            ->where('status', '!=', CollaborationStatus::Annulee)
            ->get();

        return view('livewire.admin.dashboard', [
            'activeMembers' => User::where('status', 'actif')->count(),
            'publishedFormations' => Formation::published()->count(),
            'totalFormations' => Formation::count(),
            'pendingListings' => MarketplaceListing::where('status', 'en_attente')->count(),
            'toVerify' => $toVerify,
            'revenueOnline' => $revenueOnline,
            'revenueDelivery' => $revenueDelivery,
            'revenue' => $revenueOnline + $revenueDelivery,
            'ordersToPrepare' => Order::where('status', OrderStatus::Validee)->count(),
            'ordersInDelivery' => Order::where('status', OrderStatus::Expediee)->count(),
            'newMessages' => ContactMessage::inbox()->count(),
            'bars' => $bars,
            // Section "Mise en relation" (ex-Admin\ConnectStats) — mêmes agrégats, mêmes
            // règles (volume estimé excluant les collaborations annulées, cf. §37).
            'producersTotal' => ProducerProfile::count(),
            'producersVerified' => ProducerProfile::whereNotNull('verified_at')->count(),
            'buyersTotal' => BuyerProfile::count(),
            'offersTotal' => CropOffer::count(),
            'offersPublished' => CropOffer::where('status', 'publiee')->count(),
            'needsTotal' => BuyerNeed::count(),
            'needsOpen' => BuyerNeed::where('status', 'ouvert')->count(),
            'requestsTotal' => ConnectionRequest::count(),
            'requestsActive' => ConnectionRequest::whereNotIn('status', ['refusee', 'annulee', 'collaboration_confirmee'])->count(),
            'collaborationsTotal' => Collaboration::count(),
            'collaborationsOngoing' => Collaboration::whereNotIn('status', ['terminee', 'annulee', 'litige'])->count(),
            'collaborationsDone' => Collaboration::where('status', 'terminee')->count(),
            'collaborationsDisputed' => Collaboration::where('status', 'litige')->count(),
            'estimatedVolume' => $collaborations->sum(fn (Collaboration $c) => $c->estimatedValue()),
            // Parcours de commande structuré (§1-§10) — nouvelle section, distincte de la
            // mise en relation ci-dessus (deux systèmes parallèles, cf. CLAUDE.md).
            'cropOrdersTotal' => CropOrder::count(),
            'cropOrdersPending' => CropOrder::where('status', CropOrderStatus::EnAttenteProducteur)->count(),
            'cropOrdersInNegotiation' => CropOrder::where('status', CropOrderStatus::NegociationLivraison)->count(),
            'cropOrdersConfirmed' => CropOrder::where('status', CropOrderStatus::CommandeConfirmee)->count(),
            'cropOrdersInDelivery' => CropOrder::whereIn('status', [
                CropOrderStatus::AideLivraison, CropOrderStatus::LivraisonEnPreparation, CropOrderStatus::LivraisonEnCours,
            ])->count(),
            'cropOrdersDone' => CropOrder::where('status', CropOrderStatus::Livree)->count(),
            'cropOrdersVolume' => (int) CropOrder::where('status', CropOrderStatus::Livree)->sum('total_amount'),
            'deliveryAssistsPending' => DeliveryAssist::whereNotIn('status', ['livree', 'annulee'])->count(),
        ]);
    }
}
