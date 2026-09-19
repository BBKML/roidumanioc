<?php

namespace Tests\Feature\Connect;

use App\Actions\CreateConnectionRequest;
use App\Livewire\Connect\RequestNeed;
use App\Livewire\Connect\RequestOffer;
use App\Livewire\Connect\Show;
use App\Models\BuyerNeed;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ConnectionRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected CropOffer $offer;

    protected User $buyerUser;

    protected BuyerProfile $buyerProfile;

    protected BuyerNeed $need;

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
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        $this->buyerProfile = BuyerProfile::create([
            'user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);
        $this->need = BuyerNeed::create([
            'buyer_profile_id' => $this->buyerProfile->id, 'product_wanted' => 'Manioc frais',
            'quantity' => 50, 'unit' => 'kg', 'location' => 'Abidjan', 'frequency' => 'ponctuel',
            'status' => 'ouvert',
        ]);
    }

    /** Crée une demande acheteur -> offre, directement en base (statut en_attente). */
    protected function createRequestOnOffer(): ConnectionRequest
    {
        return app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour, intéressé.');
    }

    /** Crée une demande producteur -> besoin, directement en base (statut en_attente). */
    protected function createRequestOnNeed(): ConnectionRequest
    {
        return app(CreateConnectionRequest::class)->handle($this->producerUser, 'producteur', $this->need, 'Je peux fournir.');
    }

    /* ------------------------------------------------------------------ *
     |  Création
     * ------------------------------------------------------------------ */

    public function test_buyer_creates_a_request_on_an_offer_via_the_livewire_form(): void
    {
        Livewire::actingAs($this->buyerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->set('message', 'Je suis intéressé par cette offre.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertRedirect();

        $cr = ConnectionRequest::firstOrFail();
        $this->assertSame('acheteur', $cr->requester_role);
        $this->assertSame($this->buyerUser->id, $cr->requester_user_id);
        $this->assertSame($this->producerProfile->id, $cr->producer_profile_id);
        $this->assertSame($this->buyerProfile->id, $cr->buyer_profile_id);
        $this->assertSame($this->offer->id, $cr->crop_offer_id);
        $this->assertNull($cr->buyer_need_id);
        $this->assertSame('en_attente', $cr->status->value);
    }

    public function test_producer_creates_a_request_on_a_need_via_the_livewire_form(): void
    {
        Livewire::actingAs($this->producerUser)->test(RequestNeed::class, ['need' => $this->need])
            ->set('message', 'Je peux vous fournir.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertRedirect();

        $cr = ConnectionRequest::firstOrFail();
        $this->assertSame('producteur', $cr->requester_role);
        $this->assertSame($this->producerUser->id, $cr->requester_user_id);
        $this->assertSame($this->buyerNeedIdOf($cr), $this->need->id);
        $this->assertNull($cr->crop_offer_id);
    }

    private function buyerNeedIdOf(ConnectionRequest $cr): ?int
    {
        return $cr->buyer_need_id;
    }

    /**
     * L'acheteur connaît déjà la quantité/le prix de l'offre en arrivant sur l'écran de
     * contact (Connect\RequestOffer) — il peut les indiquer tout de suite plutôt que de
     * devoir retourner les retaper plus tard dans le chat (retour utilisateur).
     */
    public function test_initial_proposal_terms_are_captured_at_first_contact(): void
    {
        Livewire::actingAs($this->buyerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->set('quantity', 8)
            ->set('priceTotal', 120000)
            ->call('send')
            ->assertHasNoErrors();

        $cr = ConnectionRequest::firstOrFail();
        $this->assertSame(8.0, (float) $cr->initial_proposal_terms['quantity']);
        $this->assertSame('kg', $cr->initial_proposal_terms['unit']);
        $this->assertSame(120000, $cr->initial_proposal_terms['price_total']);
    }

    public function test_no_initial_proposal_terms_when_the_fields_are_cleared(): void
    {
        Livewire::actingAs($this->buyerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->set('quantity', null)
            ->set('priceTotal', null)
            ->call('send')
            ->assertHasNoErrors();

        $this->assertNull(ConnectionRequest::firstOrFail()->initial_proposal_terms);
    }

    /**
     * Cœur de la fonctionnalité : quand l'acheteur a déjà indiqué ses termes à l'amorce, le
     * clic « Accepter » du producteur VEUT DIRE qu'il est d'accord avec — pas seulement
     * qu'il veut en discuter. Un seul clic enchaîne donc accept() + moveToNegotiation() +
     * propose() (au nom de l'acheteur, auteur des termes) + confirmCollaboration() (au nom
     * du producteur) : la collaboration est directement confirmée, sans étape intermédiaire
     * à valider séparément (retour utilisateur explicite : « si le client est d'accord, il
     * accepte et on passe à l'étape fin »).
     */
    public function test_accepting_a_request_with_initial_proposal_terms_confirms_the_collaboration_in_one_click(): void
    {
        Livewire::actingAs($this->buyerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->set('quantity', 8)
            ->set('priceTotal', 120000)
            ->call('send');

        $cr = ConnectionRequest::firstOrFail();

        Livewire::actingAs($this->producerUser)->test(Show::class, ['connectionRequest' => $cr])
            ->call('accept')
            ->assertHasNoErrors();

        $cr->refresh();
        $this->assertSame('collaboration_confirmee', $cr->status->value);

        $proposal = $cr->latestProposal();
        $this->assertNotNull($proposal);
        $this->assertSame($this->buyerUser->id, $proposal->sender_id);
        $this->assertSame(8.0, (float) $proposal->proposal_terms['quantity']);
        $this->assertSame('kg', $proposal->proposal_terms['unit']);
        $this->assertSame(120000, $proposal->proposal_terms['price_total']);

        $collaboration = Collaboration::where('connection_request_id', $cr->id)->firstOrFail();
        $this->assertSame(8.0, (float) $collaboration->agreed_quantity);
        $this->assertSame(120000, $collaboration->agreed_price_total);
        $this->assertNotNull($collaboration->delivery);
    }

    /**
     * Sans termes indiqués à l'amorce, rien à confirmer automatiquement — le clic
     * « Accepter » s'arrête à la négociation, exactement comme avant cette fonctionnalité
     * (comportement inchangé, cf. test_receiver_accepts_from_the_detail_screen).
     */
    public function test_accepting_a_plain_request_without_initial_terms_only_advances_to_negotiation(): void
    {
        $cr = $this->createRequestOnOffer();

        Livewire::actingAs($this->producerUser)->test(Show::class, ['connectionRequest' => $cr])
            ->call('accept')
            ->assertHasNoErrors();

        $cr->refresh();
        $this->assertSame('negociation', $cr->status->value);
        $this->assertNull($cr->latestProposal());
        $this->assertSame(0, Collaboration::where('connection_request_id', $cr->id)->count());
    }

    /**
     * Signalé en usage réel : avant acceptation, le fil de discussion ne montrait nulle
     * part la quantité/le prix indiqués à l'amorce — seul le producteur qui ouvrait la
     * page « Détails »/résumé pouvait éventuellement le voir, et le chat lui-même semblait
     * vide. Un bandeau dédié (pas une bulle de message) doit apparaître tant qu'aucune
     * vraie proposition n'existe encore, et disparaître une fois celle-ci créée (à
     * l'acceptation), remplacé par la vraie carte.
     */
    public function test_the_pending_initial_proposal_is_visible_in_the_chat_before_acceptance(): void
    {
        Livewire::actingAs($this->buyerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->set('message', '')
            ->set('quantity', 8)
            ->set('priceTotal', 120000)
            ->call('send');

        $cr = ConnectionRequest::firstOrFail();

        Livewire::actingAs($this->producerUser)->test(\App\Livewire\Connect\Conversation::class, ['connectionRequest' => $cr])
            ->assertSee('propose')
            ->assertSee('8,00 Kg', false)
            ->assertSee('120 000 FCFA', false);

        Livewire::actingAs($this->producerUser)->test(Show::class, ['connectionRequest' => $cr])
            ->call('accept');

        Livewire::actingAs($this->producerUser)->test(\App\Livewire\Connect\Conversation::class, ['connectionRequest' => $cr->fresh()])
            ->assertDontSee('transmis comme une vraie proposition');
    }

    /**
     * Audit UX : le message tapé à l'amorce ("message_initial") n'apparaissait nulle part
     * dans le fil de discussion — seulement dans un encart "Détails" séparé — donnant
     * l'impression que la conversation était vide alors qu'un texte avait bien été échangé
     * (signalé en usage réel : "je vois même pas le message... comment acceptée alors").
     * Il doit désormais être la toute première bulle du fil.
     */
    public function test_the_initial_message_becomes_the_first_chat_message(): void
    {
        $cr = $this->createRequestOnOffer();

        $conversation = Conversation::where('connection_request_id', $cr->id)->firstOrFail();
        $message = $conversation->messages()->firstOrFail();

        $this->assertSame('Bonjour, intéressé.', $message->body);
        $this->assertSame($this->buyerUser->id, $message->sender_id);
        $this->assertSame(1, $conversation->messages()->count());
    }

    public function test_no_conversation_is_created_when_no_initial_message_is_provided(): void
    {
        app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $this->offer);

        $this->assertSame(0, Conversation::count());
    }

    /**
     * "Nouvelle demande" reste LA notification de cette action — une seconde notification
     * "nouveau message" pour ce même geste serait redondante (§24, sans sur-notifier).
     */
    public function test_the_initial_message_does_not_trigger_a_duplicate_notification(): void
    {
        $this->createRequestOnOffer();

        $this->producerUser->refresh();
        $this->assertSame(1, $this->producerUser->notifications()->count());
        $this->assertSame(
            'connection_request_created',
            $this->producerUser->notifications()->first()->data['type'],
        );
    }

    /** Le message initial reste malgré tout soumis à la détection de coordonnées (§16). */
    public function test_the_initial_message_is_still_scanned_for_contact_info(): void
    {
        $cr = app(CreateConnectionRequest::class)->handle(
            $this->buyerUser, 'acheteur', $this->offer, 'Appelez-moi au 07 00 00 00 00.',
        );

        $message = Conversation::where('connection_request_id', $cr->id)
            ->firstOrFail()->messages()->firstOrFail();

        $this->assertTrue($message->contains_flagged_content);
        $this->assertStringNotContainsString('07 00 00 00 00', $message->displayBody());
    }

    /**
     * Audit UX (Phase 3) : l'ancien texte promettait un partage de coordonnées après
     * acceptation, ce qui ne se produit jamais réellement (messagerie + collaboration
     * masquent systématiquement les coordonnées). Le nouveau texte est cohérent avec le
     * reste de l'app (catalogues publics, e-mail de confirmation de collaboration).
     */
    public function test_the_request_forms_no_longer_promise_contact_sharing(): void
    {
        $expected = 'La mise en relation se fait via la plateforme — vos coordonnées personnelles ne sont jamais partagées automatiquement.';

        Livewire::actingAs($this->buyerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->assertSee($expected)
            ->assertDontSee('ne sont partagées qu\'une fois la demande acceptée');

        Livewire::actingAs($this->producerUser)->test(RequestNeed::class, ['need' => $this->need])
            ->assertSee($expected)
            ->assertDontSee('ne sont partagées qu\'une fois la demande acceptée');
    }

    public function test_producer_cannot_contact_their_own_offer(): void
    {
        Livewire::actingAs($this->producerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->assertForbidden();
    }

    public function test_buyer_cannot_respond_to_their_own_need(): void
    {
        Livewire::actingAs($this->buyerUser)->test(RequestNeed::class, ['need' => $this->need])
            ->assertForbidden();
    }

    public function test_duplicate_open_request_is_rejected(): void
    {
        $this->createRequestOnOffer();

        $this->expectException(ValidationException::class);
        app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $this->offer);
    }

    /**
     * Audit UX : l'erreur de doublon ne doit jamais apparaître sous le champ "message"
     * (facultatif) du formulaire — elle n'a rien à voir avec le texte tapé par l'utilisateur.
     */
    public function test_duplicate_open_request_error_is_not_attached_to_the_message_field(): void
    {
        $this->createRequestOnOffer();

        Livewire::actingAs($this->buyerUser)->test(RequestOffer::class, ['offer' => $this->offer])
            ->set('message', 'Encore intéressé.')
            ->call('send')
            ->assertHasErrors('general')
            ->assertHasNoErrors('message');
    }

    public function test_a_new_request_is_allowed_once_the_previous_one_is_refused(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->refuse($this->producerUser);

        $second = app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $this->offer);

        $this->assertNotSame($cr->id, $second->id);
        $this->assertSame(2, ConnectionRequest::count());
    }

    public function test_a_connection_request_must_reference_exactly_one_offer_or_need(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConnectionRequest::create([
            'requester_user_id' => $this->buyerUser->id, 'requester_role' => 'acheteur',
            'producer_profile_id' => $this->producerProfile->id, 'buyer_profile_id' => $this->buyerProfile->id,
            'crop_offer_id' => $this->offer->id, 'buyer_need_id' => $this->need->id,
        ]);
    }

    public function test_a_connection_request_cannot_reference_neither_offer_nor_need(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConnectionRequest::create([
            'requester_user_id' => $this->buyerUser->id, 'requester_role' => 'acheteur',
            'producer_profile_id' => $this->producerProfile->id, 'buyer_profile_id' => $this->buyerProfile->id,
        ]);
    }

    /* ------------------------------------------------------------------ *
     |  Cycle complet — chemin nominal
     * ------------------------------------------------------------------ */

    public function test_a_request_travels_through_the_full_happy_path(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertTrue($cr->accept($this->producerUser));
        $this->assertSame('acceptee', $cr->fresh()->status->value);

        $this->assertTrue($cr->moveToNegotiation($this->buyerUser));
        $this->assertSame('negociation', $cr->fresh()->status->value);

        $this->assertTrue($cr->propose($this->producerUser));
        $this->assertSame('proposition', $cr->fresh()->status->value);

        $this->assertTrue($cr->confirmCollaboration($this->buyerUser));
        $this->assertSame('collaboration_confirmee', $cr->fresh()->status->value);

        // Journalisé à chaque transition (LogsActivity).
        $this->assertSame(5, $cr->activities()->count()); // création + 4 transitions
    }

    /* ------------------------------------------------------------------ *
     |  accept()
     * ------------------------------------------------------------------ */

    public function test_receiver_can_accept_a_pending_request(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertTrue($cr->accept($this->producerUser));
        $this->assertSame('acceptee', $cr->fresh()->status->value);
    }

    public function test_requester_cannot_accept_their_own_request(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertFalse($cr->accept($this->buyerUser));
        $this->assertSame('en_attente', $cr->fresh()->status->value);
    }

    public function test_a_third_party_cannot_accept_a_request(): void
    {
        $cr = $this->createRequestOnOffer();
        $intruder = User::factory()->create();

        $this->assertFalse($cr->accept($intruder));
        $this->assertSame('en_attente', $cr->fresh()->status->value);
    }

    public function test_accept_is_a_no_op_once_already_accepted(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);

        $this->assertFalse($cr->accept($this->producerUser));
        $this->assertSame('acceptee', $cr->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  cancel()
     * ------------------------------------------------------------------ */

    public function test_requester_can_cancel_their_pending_request(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertTrue($cr->cancel($this->buyerUser));
        $this->assertSame('annulee', $cr->fresh()->status->value);
    }

    public function test_receiver_cannot_cancel_a_request_they_did_not_send(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertFalse($cr->cancel($this->producerUser));
        $this->assertSame('en_attente', $cr->fresh()->status->value);
    }

    public function test_requester_cannot_cancel_once_the_request_has_been_accepted(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);

        $this->assertFalse($cr->cancel($this->buyerUser));
        $this->assertSame('acceptee', $cr->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  refuse()
     * ------------------------------------------------------------------ */

    public function test_receiver_can_refuse_a_pending_request(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertTrue($cr->refuse($this->producerUser));
        $this->assertSame('refusee', $cr->fresh()->status->value);
    }

    public function test_requester_cannot_refuse_their_own_pending_request(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertFalse($cr->refuse($this->buyerUser));
        $this->assertSame('en_attente', $cr->fresh()->status->value);
    }

    public function test_either_party_can_refuse_while_accepted(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);

        $this->assertTrue($cr->refuse($this->producerUser));
        $this->assertSame('refusee', $cr->fresh()->status->value);
    }

    public function test_either_party_can_refuse_during_negotiation(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $cr->moveToNegotiation($this->buyerUser);

        $this->assertTrue($cr->refuse($this->buyerUser));
        $this->assertSame('refusee', $cr->fresh()->status->value);
    }

    /** Symétrique du test ci-dessus — l'autre partie doit aussi pouvoir refuser depuis `negociation`. */
    public function test_the_producer_can_also_refuse_during_negotiation(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $cr->moveToNegotiation($this->buyerUser);

        $this->assertTrue($cr->refuse($this->producerUser));
        $this->assertSame('refusee', $cr->fresh()->status->value);
    }

    public function test_either_party_can_refuse_a_proposition(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $cr->moveToNegotiation($this->buyerUser);
        $cr->propose($this->producerUser);

        $this->assertTrue($cr->refuse($this->buyerUser));
        $this->assertSame('refusee', $cr->fresh()->status->value);
    }

    public function test_a_third_party_cannot_refuse_a_request(): void
    {
        $cr = $this->createRequestOnOffer();
        $intruder = User::factory()->create();

        $this->assertFalse($cr->refuse($intruder));
        $this->assertSame('en_attente', $cr->fresh()->status->value);
    }

    public function test_refuse_is_a_no_op_once_collaboration_is_confirmed(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $cr->moveToNegotiation($this->buyerUser);
        $cr->propose($this->producerUser);
        $cr->confirmCollaboration($this->buyerUser);

        $this->assertFalse($cr->refuse($this->producerUser));
        $this->assertSame('collaboration_confirmee', $cr->fresh()->status->value);
    }

    public function test_refuse_is_a_no_op_once_already_refused(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->refuse($this->producerUser);

        $this->assertFalse($cr->refuse($this->producerUser));
        $this->assertSame('refusee', $cr->fresh()->status->value);
    }

    public function test_refuse_is_a_no_op_once_already_cancelled(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->cancel($this->buyerUser);

        $this->assertFalse($cr->refuse($this->producerUser));
        $this->assertSame('annulee', $cr->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  moveToNegotiation() / propose() / confirmCollaboration()
     * ------------------------------------------------------------------ */

    public function test_move_to_negotiation_requires_the_accepted_status(): void
    {
        $cr = $this->createRequestOnOffer();

        $this->assertFalse($cr->moveToNegotiation($this->producerUser));
        $this->assertSame('en_attente', $cr->fresh()->status->value);
    }

    public function test_a_third_party_cannot_move_the_request_to_negotiation(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $intruder = User::factory()->create();

        $this->assertFalse($cr->moveToNegotiation($intruder));
        $this->assertSame('acceptee', $cr->fresh()->status->value);
    }

    public function test_propose_requires_the_negotiation_status(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);

        $this->assertFalse($cr->propose($this->producerUser));
        $this->assertSame('acceptee', $cr->fresh()->status->value);
    }

    public function test_a_third_party_cannot_propose_a_deal(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $cr->moveToNegotiation($this->buyerUser);
        $intruder = User::factory()->create();

        $this->assertFalse($cr->propose($intruder));
        $this->assertSame('negociation', $cr->fresh()->status->value);
    }

    public function test_confirm_collaboration_requires_the_proposition_status(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $cr->moveToNegotiation($this->buyerUser);

        $this->assertFalse($cr->confirmCollaboration($this->buyerUser));
        $this->assertSame('negociation', $cr->fresh()->status->value);
    }

    public function test_a_third_party_cannot_confirm_the_collaboration(): void
    {
        $cr = $this->createRequestOnOffer();
        $cr->accept($this->producerUser);
        $cr->moveToNegotiation($this->buyerUser);
        $cr->propose($this->producerUser);
        $intruder = User::factory()->create();

        $this->assertFalse($cr->confirmCollaboration($intruder));
        $this->assertSame('proposition', $cr->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  Policy / accès à l'écran de détail
     * ------------------------------------------------------------------ */

    public function test_both_parties_can_view_the_request_detail_screen(): void
    {
        $cr = $this->createRequestOnOffer();

        Livewire::actingAs($this->buyerUser)->test(Show::class, ['connectionRequest' => $cr])->assertOk();
        Livewire::actingAs($this->producerUser)->test(Show::class, ['connectionRequest' => $cr])->assertOk();
    }

    public function test_a_third_party_cannot_view_the_request_detail_screen(): void
    {
        $cr = $this->createRequestOnOffer();
        $intruder = User::factory()->create();

        Livewire::actingAs($intruder)->test(Show::class, ['connectionRequest' => $cr])->assertForbidden();
    }

    /**
     * Accepter et passer en négociation sont fusionnés en un seul clic (audit UX) : le
     * bouton "Accepter" du panneau enchaîne désormais directement accept() puis
     * moveToNegotiation() — le modèle garde les deux transitions séparées (testées
     * isolément ailleurs), seul cet écran les enchaîne.
     */
    public function test_receiver_accepts_from_the_detail_screen(): void
    {
        $cr = $this->createRequestOnOffer();

        Livewire::actingAs($this->producerUser)->test(Show::class, ['connectionRequest' => $cr])
            ->call('accept')
            ->assertHasNoErrors();

        $this->assertSame('negociation', $cr->fresh()->status->value);
    }

    public function test_requester_cannot_accept_from_the_detail_screen(): void
    {
        $cr = $this->createRequestOnOffer();

        Livewire::actingAs($this->buyerUser)->test(Show::class, ['connectionRequest' => $cr])
            ->call('accept')
            ->assertForbidden();

        $this->assertSame('en_attente', $cr->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  Confidentialité (§14 / §8.2)
     * ------------------------------------------------------------------ */

    public function test_no_personal_contact_information_leaks_on_the_detail_screen(): void
    {
        $producer = User::factory()->create(['phone' => '0700000077', 'email' => 'secret-producer@example.com']);
        $buyer = User::factory()->create(['phone' => '0700000066', 'email' => 'secret-buyer@example.com']);
        $producerProfile = ProducerProfile::create([
            'user_id' => $producer->id, 'business_name' => 'Ferme confidentielle',
            'zone' => 'Bouaké', 'activity_type' => 'recolte',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc confidentiel',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Bouaké',
            'status' => 'publiee', 'is_available' => true,
        ]);
        BuyerProfile::create(['user_id' => $buyer->id, 'buyer_type' => 'restaurant', 'zone' => 'Abidjan']);
        $cr = app(CreateConnectionRequest::class)->handle($buyer, 'acheteur', $offer);

        $this->actingAs($producer)->get(route('learner.requests.show', $cr))
            ->assertOk()
            ->assertDontSee('0700000077')
            ->assertDontSee('secret-producer@example.com')
            ->assertDontSee('0700000066')
            ->assertDontSee('secret-buyer@example.com');
    }
}
