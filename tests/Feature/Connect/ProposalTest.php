<?php

namespace Tests\Feature\Connect;

use App\Actions\CreateConnectionRequest;
use App\Livewire\Connect\Conversation as ConversationComponent;
use App\Livewire\Connect\Show as ConnectShow;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Négociation façon WhatsApp (Phase 14) : « Faire une proposition » envoie désormais une
 * carte structurée dans la messagerie plutôt qu'un simple bouton générique. La transition
 * de statut sous-jacente (`propose()`/`confirmCollaboration()`/`refuse()`) est inchangée —
 * ces tests vérifient l'intégration (message créé + statut avancé + accept/refuse depuis
 * le chat), pas la machine à états elle-même (déjà couverte par ConnectionRequestTest).
 */
class ProposalTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected User $buyerUser;

    protected ConnectionRequest $connectionRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $this->connectionRequest = app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $offer);
        $this->connectionRequest->accept($this->producerUser);
        $this->connectionRequest->moveToNegotiation($this->buyerUser);
    }

    public function test_a_party_sends_a_structured_proposal_which_creates_a_card_and_advances_the_status(): void
    {
        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->call('startProposal')
            ->set('proposalQuantity', '5')
            ->set('proposalUnit', 'tonne')
            ->set('proposalPrice', '500000')
            ->set('proposalNote', 'Récolte de cette semaine.')
            ->call('sendProposal')
            ->assertHasNoErrors()
            ->assertSet('showProposalForm', false)
            ->assertSee('Récolte de cette semaine.')
            ->assertSee('500 000 FCFA', false);

        $this->assertSame('proposition', $this->connectionRequest->fresh()->status->value);

        $message = ConversationMessage::firstOrFail();
        $this->assertSame('proposition', $message->type);
        $this->assertSame(5.0, (float) $message->proposal_terms['quantity']);
        $this->assertSame('tonne', $message->proposal_terms['unit']);
        $this->assertSame(500000, $message->proposal_terms['price_total']);
    }

    public function test_the_note_is_optional(): void
    {
        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->call('startProposal')
            ->set('proposalQuantity', '5')
            ->set('proposalUnit', 'tonne')
            ->call('sendProposal')
            ->assertHasNoErrors();

        $this->assertSame('proposition', $this->connectionRequest->fresh()->status->value);
        $this->assertSame('', ConversationMessage::firstOrFail()->body);
    }

    /**
     * Une fois en "proposition", la même ability `propose` (ConnectionRequestPolicy)
     * refuse déjà l'accès — même garde-fou que le bouton générique d'origine, avant même
     * d'atteindre la validation des champs.
     */
    public function test_sending_a_proposal_requires_the_negotiation_status(): void
    {
        $this->connectionRequest->propose($this->producerUser); // déjà en "proposition"

        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('proposalQuantity', '5')
            ->set('proposalUnit', 'tonne')
            ->call('sendProposal')
            ->assertForbidden();

        $this->assertSame(0, ConversationMessage::count());
    }

    public function test_a_third_party_cannot_send_a_proposal(): void
    {
        $intruder = User::factory()->create();

        Livewire::actingAs($intruder)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('proposalQuantity', '5')
            ->set('proposalUnit', 'tonne')
            ->call('sendProposal')
            ->assertForbidden();

        $this->assertSame(0, ConversationMessage::count());
        $this->assertSame('negociation', $this->connectionRequest->fresh()->status->value);
    }

    public function test_invalid_terms_are_rejected(): void
    {
        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('proposalQuantity', '0')
            ->set('proposalUnit', 'tonne')
            ->call('sendProposal')
            ->assertHasErrors('proposalQuantity');

        $this->assertSame(0, ConversationMessage::count());
        $this->assertSame('negociation', $this->connectionRequest->fresh()->status->value);
    }

    /** L'erreur de validation sur le prix doit s'afficher sous le bon champ (proposalPrice). */
    public function test_an_invalid_price_is_flagged_on_the_price_field(): void
    {
        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('proposalQuantity', '5')
            ->set('proposalUnit', 'tonne')
            ->set('proposalPrice', '-100')
            ->call('sendProposal')
            ->assertHasErrors('proposalPrice');

        $this->assertSame(0, ConversationMessage::count());
    }

    /** §16 : la note d'une proposition reste soumise à la même détection/masquage qu'un message ordinaire. */
    public function test_the_proposal_note_is_still_scanned_for_contact_info(): void
    {
        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('proposalQuantity', '5')
            ->set('proposalUnit', 'tonne')
            ->set('proposalNote', 'Appelez-moi au 07 00 00 00 00 pour finaliser.')
            ->call('sendProposal')
            ->assertHasNoErrors();

        $message = ConversationMessage::firstOrFail();
        $this->assertTrue($message->contains_flagged_content);
        $this->assertStringNotContainsString('07 00 00 00 00', $message->displayBody());
    }

    public function test_the_other_party_accepts_the_proposal_from_the_chat_and_a_collaboration_is_created(): void
    {
        $this->connectionRequest->propose($this->producerUser);
        $conversation = Conversation::firstOrCreate(['connection_request_id' => $this->connectionRequest->id]);
        $conversation->messages()->create([
            'sender_id' => $this->producerUser->id, 'type' => 'proposition', 'body' => '',
            'proposal_terms' => ['quantity' => 5, 'unit' => 'tonne', 'price_total' => 500000],
        ]);

        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->call('acceptProposal')
            ->assertHasNoErrors();

        $this->connectionRequest->refresh();
        $this->assertSame('collaboration_confirmee', $this->connectionRequest->status->value);

        $collaboration = Collaboration::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();
        $this->assertNotNull($collaboration->delivery);
    }

    /**
     * Bug remonté en usage réel (capture d'écran) : l'auteur d'une proposition voyait aussi
     * le bouton « Accepter » sur sa propre carte dans le fil — accepter sa propre proposition
     * n'a pas de sens. `canConfirmCollaborationBy()` compare désormais à l'auteur du dernier
     * message de type proposition, pas seulement à l'appartenance à la demande.
     */
    public function test_the_author_of_the_proposal_cannot_accept_their_own_proposal(): void
    {
        $this->connectionRequest->propose($this->producerUser);
        $conversation = Conversation::firstOrCreate(['connection_request_id' => $this->connectionRequest->id]);
        $conversation->messages()->create([
            'sender_id' => $this->producerUser->id, 'type' => 'proposition', 'body' => '',
            'proposal_terms' => ['quantity' => 5, 'unit' => 'tonne', 'price_total' => 500000],
        ]);

        $this->assertFalse($this->connectionRequest->canConfirmCollaborationBy($this->producerUser));
        $this->assertTrue($this->connectionRequest->canConfirmCollaborationBy($this->buyerUser));

        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->assertDontSee('>Accepter<', false)
            ->call('acceptProposal')
            ->assertForbidden();

        $this->assertSame('proposition', $this->connectionRequest->fresh()->status->value);
        $this->assertSame(0, Collaboration::count());

        // Le panneau latéral partagé (Connect\Show) utilise la même méthode de garde —
        // pas de logique dupliquée qui pourrait diverger.
        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->assertDontSee('Confirmer la collaboration');
    }

    public function test_either_party_can_refuse_the_proposal_from_the_chat(): void
    {
        $this->connectionRequest->propose($this->producerUser);

        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->call('refuseProposal');

        $this->assertSame('refusee', $this->connectionRequest->fresh()->status->value);
        $this->assertSame(0, Collaboration::count());
    }

    /** L'écran principal reste synchronisé après une action déclenchée depuis le chat (enfant). */
    public function test_the_parent_screen_reflects_the_status_change_made_from_the_chat(): void
    {
        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->assertSee('Négociation');

        $this->connectionRequest->propose($this->producerUser);

        Livewire::actingAs($this->buyerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('refreshConnectionRequest')
            ->assertSee('Proposition');
    }

    /** Le bouton générique "Faire une proposition" a été retiré du panneau — la proposition se fait dans le chat. */
    public function test_the_generic_propose_button_no_longer_exists_on_the_main_screen(): void
    {
        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->assertDontSee('wire:click="propose"', false);
    }

    /**
     * Vérifie le rendu complet via une vraie requête HTTP (layout + composant enfant
     * Conversation imbriqué) — pas seulement les composants Livewire testés isolément
     * ci-dessus, pour s'assurer que la page entière (nouvelle mise en page plein cadre)
     * compile et s'affiche sans erreur dans son contexte réel.
     */
    public function test_the_full_page_renders_over_http_with_the_new_layout(): void
    {
        $this->actingAs($this->producerUser)
            ->get(route('learner.requests.show', $this->connectionRequest))
            ->assertOk()
            ->assertSee('connect-shell', false)
            ->assertSee('chat-panel', false)
            ->assertSee('Proposer');

        $this->connectionRequest->propose($this->producerUser);
        Conversation::firstOrCreate(['connection_request_id' => $this->connectionRequest->id])
            ->messages()->create([
                'sender_id' => $this->producerUser->id, 'type' => 'proposition', 'body' => '',
                'proposal_terms' => ['quantity' => 5, 'unit' => 'tonne', 'price_total' => 500000],
            ]);

        $this->actingAs($this->buyerUser)
            ->get(route('learner.requests.show', $this->connectionRequest))
            ->assertOk()
            ->assertSee('Accepter')
            ->assertSee('Refuser');
    }
}
