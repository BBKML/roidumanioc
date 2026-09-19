<?php

namespace Tests\Feature\CropOrder;

use App\Actions\CreateCropOrder;
use App\Enums\CropOrderStatus;
use App\Enums\DeliveryAssistStatus;
use App\Enums\DeliveryProposalStatus;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\CropOrder;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CropOrderTransitionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected CropOffer $offer;

    protected User $buyerUser;

    protected BuyerProfile $buyerProfile;

    protected User $admin;

    protected User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producerUser = User::factory()->create();
        $this->producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $this->offer = CropOffer::create([
            'producer_profile_id' => $this->producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 100, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        $this->buyerProfile = BuyerProfile::create([
            'user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'actif']);
        $this->stranger = User::factory()->create();
    }

    protected function makeOrder(): CropOrder
    {
        return app(CreateCropOrder::class)->handle(
            $this->buyerUser, $this->offer, 20.0, 'kg', 'Cocody', null, null, 'Mobile Money', null, null,
        );
    }

    /* ------------------------------------------------------------------ *
     |  accept / refuse
     * ------------------------------------------------------------------ */

    public function test_the_producer_can_accept_a_pending_order(): void
    {
        $order = $this->makeOrder();

        $this->assertTrue($order->accept($this->producerUser));
        $this->assertSame(CropOrderStatus::Acceptee, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->accepted_at);
    }

    public function test_the_buyer_cannot_accept_their_own_order(): void
    {
        $order = $this->makeOrder();

        $this->assertFalse($order->accept($this->buyerUser));
        $this->assertSame(CropOrderStatus::EnAttenteProducteur, $order->fresh()->status);
    }

    public function test_a_stranger_cannot_accept_an_order(): void
    {
        $order = $this->makeOrder();

        $this->assertFalse($order->accept($this->stranger));
    }

    public function test_accept_is_idempotent_once_already_accepted(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        $this->assertFalse($order->fresh()->accept($this->producerUser));
    }

    public function test_the_producer_can_refuse_with_a_reason(): void
    {
        $order = $this->makeOrder();

        $this->assertTrue($order->refuse($this->producerUser, 'Rupture de stock'));
        $order->refresh();
        $this->assertSame(CropOrderStatus::Refusee, $order->status);
        $this->assertSame('Rupture de stock', $order->refusal_reason);
    }

    public function test_refuse_is_only_possible_from_the_pending_status(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        $this->assertFalse($order->fresh()->refuse($this->producerUser));
    }

    /* ------------------------------------------------------------------ *
     |  cancel
     * ------------------------------------------------------------------ */

    public function test_the_buyer_can_cancel_a_pending_order(): void
    {
        $order = $this->makeOrder();

        $this->assertTrue($order->cancel($this->buyerUser));
        $this->assertSame(CropOrderStatus::Annulee, $order->fresh()->status);
    }

    public function test_the_producer_cannot_cancel_a_still_pending_order(): void
    {
        $order = $this->makeOrder();

        $this->assertFalse($order->cancel($this->producerUser));
    }

    public function test_either_party_can_cancel_during_negotiation(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        $this->assertTrue($order->fresh()->cancel($this->producerUser));
    }

    public function test_cancel_is_impossible_once_confirmed(): void
    {
        $order = $this->fullyNegotiatedOrder();

        $this->assertFalse($order->fresh()->cancel($this->buyerUser));
    }

    /* ------------------------------------------------------------------ *
     |  submitDeliveryConditionsForReview / approve / reject (validation admin)
     * ------------------------------------------------------------------ */

    public function test_the_producer_submits_delivery_conditions_for_admin_review(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        $this->assertTrue($order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000, 'Livraison sous 3 jours'));

        $order->refresh();
        $this->assertSame(CropOrderStatus::EnAttenteValidationAdmin, $order->status);
        $this->assertSame(300000, $order->pending_product_price_total);
        $this->assertSame(25000, $order->pending_delivery_fee);
        // Rien n'est encore visible/réel côté acheteur : aucune ligne d'historique créée,
        // product_price_total pas encore fixé.
        $this->assertNull($order->product_price_total);
        $this->assertCount(0, $order->deliveryProposals);
    }

    public function test_the_buyer_cannot_submit_delivery_conditions(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        $this->assertFalse($order->fresh()->submitDeliveryConditionsForReview($this->buyerUser, 300000, 25000));
    }

    public function test_only_an_admin_can_approve_or_reject_submitted_conditions(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->refresh();

        $this->assertFalse($order->approveDeliveryConditions($this->producerUser));
        $this->assertFalse($order->approveDeliveryConditions($this->buyerUser));
        $this->assertTrue($order->approveDeliveryConditions($this->admin));
    }

    public function test_approving_releases_the_first_proposal_to_the_buyer(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000, 'Livraison sous 3 jours');
        $order = $order->fresh();

        $this->assertTrue($order->approveDeliveryConditions($this->admin, 'Prix cohérent'));

        $order->refresh();
        $this->assertSame(CropOrderStatus::NegociationLivraison, $order->status);
        $this->assertSame(300000, $order->product_price_total);
        $this->assertNull($order->pending_product_price_total);
        $this->assertNull($order->pending_delivery_fee);
        $this->assertCount(1, $order->deliveryProposals);
        $this->assertSame(25000, $order->deliveryProposals->first()->amount);
        $this->assertSame($this->producerUser->id, $order->deliveryProposals->first()->proposed_by);
        $this->assertSame(DeliveryProposalStatus::EnAttente, $order->deliveryProposals->first()->status);
    }

    public function test_rejecting_sends_the_producer_back_to_resubmit(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order = $order->fresh();

        $this->assertTrue($order->rejectDeliveryConditions($this->admin, 'Frais de livraison trop élevés'));

        $order->refresh();
        $this->assertSame(CropOrderStatus::Acceptee, $order->status);
        $this->assertNull($order->pending_product_price_total);
        $this->assertNull($order->pending_delivery_fee);
        $this->assertSame('Frais de livraison trop élevés', $order->admin_review_note);
        $this->assertCount(0, $order->deliveryProposals);

        // Le producteur peut resoumettre après un renvoi.
        $this->assertTrue($order->canSubmitDeliveryConditionsBy($this->producerUser));
        $this->assertTrue($order->submitDeliveryConditionsForReview($this->producerUser, 280000, 20000));
    }

    public function test_review_actions_are_only_possible_from_the_pending_review_status(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        $this->assertFalse($order->approveDeliveryConditions($this->admin));
        $this->assertFalse($order->rejectDeliveryConditions($this->admin, 'motif'));
    }

    /* ------------------------------------------------------------------ *
     |  proposeDeliveryFee / acceptDeliveryFee — négociation, après validation admin
     * ------------------------------------------------------------------ */

    public function test_a_counter_proposal_supersedes_the_previous_one_without_deleting_it(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order->refresh();

        $this->assertTrue($order->proposeDeliveryFee($this->buyerUser, 15000, 'Trop cher'));
        $order->refresh();

        $this->assertCount(2, $order->deliveryProposals);
        $proposals = $order->deliveryProposals()->oldest('id')->get();
        $this->assertSame(DeliveryProposalStatus::Perimee, $proposals[0]->status);
        $this->assertSame(DeliveryProposalStatus::EnAttente, $proposals[1]->status);
        $this->assertSame(15000, $proposals[1]->amount);
    }

    public function test_a_party_cannot_act_on_their_own_latest_proposal(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order->refresh();

        // Le producteur vient de proposer (via l'admin) — il ne peut ni re-proposer, ni "accepter" sa propre offre.
        $this->assertFalse($order->canProposeDeliveryFeeBy($this->producerUser));
        $this->assertFalse($order->canAcceptDeliveryFeeBy($this->producerUser));
        $this->assertTrue($order->canProposeDeliveryFeeBy($this->buyerUser));
        $this->assertTrue($order->canAcceptDeliveryFeeBy($this->buyerUser));
    }

    protected function fullyNegotiatedOrder(): CropOrder
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 15000);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->producerUser);

        return $order->fresh();
    }

    public function test_accepting_a_proposal_confirms_the_order_and_freezes_totals(): void
    {
        $order = $this->fullyNegotiatedOrder();

        $this->assertSame(CropOrderStatus::CommandeConfirmee, $order->status);
        $this->assertSame(15000, $order->delivery_fee_agreed);
        $this->assertSame(315000, $order->total_amount);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_accord_final_is_never_persisted_in_the_database(): void
    {
        $order = $this->fullyNegotiatedOrder();

        $this->assertNotSame('accord_final', $order->getRawOriginal('status'));
        $this->assertDatabaseMissing('crop_orders', ['id' => $order->id, 'status' => 'accord_final']);
    }

    /* ------------------------------------------------------------------ *
     |  requestDeliveryAssistance / markDeliveryAssistStep
     * ------------------------------------------------------------------ */

    public function test_delivery_assistance_can_only_be_requested_once_confirmed(): void
    {
        $order = $this->makeOrder();

        $this->assertFalse($order->requestDeliveryAssistance($this->buyerUser));
    }

    public function test_either_party_can_request_delivery_assistance_once_confirmed(): void
    {
        $order = $this->fullyNegotiatedOrder();

        $this->assertTrue($order->requestDeliveryAssistance($this->buyerUser));
        $order->refresh();
        $this->assertSame(CropOrderStatus::AideLivraison, $order->status);
        $this->assertNotNull($order->deliveryAssist);
        $this->assertSame(DeliveryAssistStatus::DemandeAide, $order->deliveryAssist->status);
    }

    public function test_requesting_delivery_assistance_twice_is_idempotent(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);

        $this->assertFalse($order->fresh()->requestDeliveryAssistance($this->producerUser));
        $this->assertSame(1, CropOrder::find($order->id)->deliveryAssist()->count());
    }

    public function test_only_an_admin_can_mark_a_delivery_assist_step(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order->refresh();

        $this->assertFalse($order->markDeliveryAssistStep($this->producerUser, DeliveryAssistStatus::EnPreparation));
        $this->assertTrue($order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::EnPreparation));
    }

    public function test_delivery_assist_steps_progress_in_order_and_update_the_global_status(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order->refresh();

        // Impossible de sauter une étape.
        $this->assertFalse($order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::EnCours));

        $this->assertTrue($order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::EnPreparation));
        $order->refresh();
        $this->assertSame(CropOrderStatus::LivraisonEnPreparation, $order->status);

        $this->assertTrue($order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::LivreurContacte));
        $this->assertTrue($order->fresh()->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::EnCours));
        $order->refresh();
        $this->assertSame(CropOrderStatus::LivraisonEnCours, $order->status);

        $this->assertTrue($order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::Livree));
        $order->refresh();
        $this->assertSame(CropOrderStatus::Livree, $order->status);
        $this->assertSame(DeliveryAssistStatus::Livree, $order->deliveryAssist->status);
    }

    public function test_cancelling_the_delivery_assist_returns_the_order_to_confirmed(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order->refresh();

        $this->assertTrue($order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::Annulee));
        $order->refresh();
        $this->assertSame(CropOrderStatus::CommandeConfirmee, $order->status);
        $this->assertSame(DeliveryAssistStatus::Annulee, $order->deliveryAssist->status);
    }

    /**
     * Régression (audit) : `requestDeliveryAssistance()` utilisait `firstOrCreate([], ...)`,
     * qui réutilisait la ligne `DeliveryAssist` déjà `annulee` sans réinitialiser son statut
     * — aucune transition ne sortant de `annulee`, la commande restait bloquée dans
     * `aide_livraison` pour toujours. `updateOrCreate` doit repartir de `demande_aide`.
     */
    public function test_requesting_delivery_assistance_again_after_a_cancellation_resets_it(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order->refresh();
        $order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::Annulee);
        $order->refresh();

        $this->assertTrue($order->requestDeliveryAssistance($this->buyerUser));
        $order->refresh();

        $this->assertSame(CropOrderStatus::AideLivraison, $order->status);
        $this->assertSame(DeliveryAssistStatus::DemandeAide, $order->deliveryAssist->status);
        $this->assertNull($order->deliveryAssist->cancelled_at);

        // Et la commande peut de nouveau avancer normalement (elle ne serait plus jamais
        // sortie de `annulee` avant le correctif).
        $this->assertTrue($order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::EnPreparation));
    }

    /* ------------------------------------------------------------------ *
     |  Policy — un admin non-partie ne doit jamais passer une ability party-only
     * ------------------------------------------------------------------ */

    public function test_gate_denies_party_only_abilities_to_a_non_party_admin(): void
    {
        $order = $this->makeOrder();

        $this->assertFalse(Gate::forUser($this->admin)->allows('accept', $order));
        $this->assertFalse(Gate::forUser($this->admin)->allows('refuse', $order));
        $this->assertFalse(Gate::forUser($this->admin)->allows('cancel', $order));

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $order));
    }

    public function test_gate_allows_mark_delivery_assist_step_to_admin_only(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order->refresh();

        $this->assertTrue(Gate::forUser($this->admin)->allows('markDeliveryAssistStep', [$order, DeliveryAssistStatus::EnPreparation]));
        $this->assertFalse(Gate::forUser($this->buyerUser)->allows('markDeliveryAssistStep', [$order, DeliveryAssistStatus::EnPreparation]));
    }

    public function test_gate_denies_review_abilities_to_a_non_admin(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->refresh();

        $this->assertFalse(Gate::forUser($this->producerUser)->allows('approveDeliveryConditions', $order));
        $this->assertFalse(Gate::forUser($this->buyerUser)->allows('approveDeliveryConditions', $order));
        $this->assertTrue(Gate::forUser($this->admin)->allows('approveDeliveryConditions', $order));
    }

    /* ------------------------------------------------------------------ *
     |  Livraison auto-organisée (3ᵉ choix, sans passer par l'admin)
     * ------------------------------------------------------------------ */

    public function test_the_buyer_can_declare_they_pick_up_themselves(): void
    {
        $order = $this->fullyNegotiatedOrder();

        $this->assertFalse($order->canDeclareSelfArrangedDeliveryBy($this->producerUser, 'acheteur'));
        $this->assertTrue($order->declareSelfArrangedDelivery($this->buyerUser, 'acheteur'));

        $order->refresh();
        $this->assertSame(CropOrderStatus::LivraisonAutoOrganisee, $order->status);
        $this->assertSame('acheteur', $order->self_arranged_mode);
        $this->assertSame($this->buyerUser->id, $order->self_arranged_by);
        $this->assertNotNull($order->self_arranged_at);
    }

    public function test_the_producer_can_declare_they_deliver_themselves(): void
    {
        $order = $this->fullyNegotiatedOrder();

        $this->assertFalse($order->canDeclareSelfArrangedDeliveryBy($this->buyerUser, 'producteur'));
        $this->assertTrue($order->declareSelfArrangedDelivery($this->producerUser, 'producteur'));

        $order->refresh();
        $this->assertSame(CropOrderStatus::LivraisonAutoOrganisee, $order->status);
        $this->assertSame('producteur', $order->self_arranged_mode);
    }

    public function test_self_arranged_delivery_is_only_possible_once_confirmed(): void
    {
        $order = $this->makeOrder();

        $this->assertFalse($order->declareSelfArrangedDelivery($this->buyerUser, 'acheteur'));
    }

    public function test_only_the_buyer_confirms_a_self_arranged_delivery(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->declareSelfArrangedDelivery($this->buyerUser, 'acheteur');
        $order->refresh();

        $this->assertFalse($order->confirmSelfArrangedDelivery($this->producerUser));
        $this->assertTrue($order->confirmSelfArrangedDelivery($this->buyerUser));

        $order->refresh();
        $this->assertSame(CropOrderStatus::Livree, $order->status);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_either_party_can_cancel_a_self_arranged_delivery(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->declareSelfArrangedDelivery($this->producerUser, 'producteur');
        $order->refresh();

        $this->assertTrue($order->cancelSelfArrangedDelivery($this->buyerUser));

        $order->refresh();
        $this->assertSame(CropOrderStatus::CommandeConfirmee, $order->status);
        $this->assertNull($order->self_arranged_mode);
        $this->assertNull($order->self_arranged_by);

        // Les 3 choix redeviennent possibles.
        $this->assertTrue($order->canDeclareSelfArrangedDeliveryBy($this->buyerUser, 'acheteur'));
        $this->assertTrue($order->canRequestDeliveryAssistanceBy($this->buyerUser));
    }

    public function test_self_arranged_and_admin_assisted_delivery_are_mutually_exclusive(): void
    {
        $order = $this->fullyNegotiatedOrder();
        $order->declareSelfArrangedDelivery($this->buyerUser, 'acheteur');
        $order->refresh();

        // Une fois la livraison auto-organisée déclarée, l'aide admin n'est plus disponible
        // (statut n'est plus commande_confirmee) tant que ce n'est pas annulé.
        $this->assertFalse($order->requestDeliveryAssistance($this->producerUser));
    }
}
