<?php

namespace Tests\Feature\Connect;

use App\Actions\CreateConnectionRequest;
use App\Livewire\Admin\Conversations as AdminConversations;
use App\Livewire\Connect\Conversation as ConversationComponent;
use App\Models\BuyerProfile;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessagingTest extends TestCase
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
    }

    /* ------------------------------------------------------------------ *
     |  Échange de base
     * ------------------------------------------------------------------ */

    public function test_both_parties_exchange_messages(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Bonjour, votre offre m\'intéresse.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSee('Bonjour, votre offre m\'intéresse.');

        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->assertSee('Bonjour, votre offre m\'intéresse.')
            ->set('body', 'Bonjour, oui elle est disponible.')
            ->call('send')
            ->assertHasNoErrors();

        $conversation = Conversation::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();
        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_conversation_is_created_lazily_on_first_message(): void
    {
        $this->assertSame(0, Conversation::count());

        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Premier message.')
            ->call('send');

        $this->assertSame(1, Conversation::count());
    }

    public function test_an_empty_message_is_rejected(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', '   ')
            ->call('send')
            ->assertHasErrors('body');

        $this->assertSame(0, ConversationMessage::count());
    }

    public function test_a_message_over_the_max_length_is_rejected(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', str_repeat('a', 2001))
            ->call('send')
            ->assertHasErrors('body');

        $this->assertSame(0, ConversationMessage::count());
    }

    /* ------------------------------------------------------------------ *
     |  Autorisation
     * ------------------------------------------------------------------ */

    public function test_a_third_party_cannot_view_the_composer_or_send(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Message confidentiel entre les deux parties.')
            ->call('send');

        $intruder = User::factory()->create();

        Livewire::actingAs($intruder)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->assertSet('canSend', false)
            // Le composant reste montable (jamais routé directement aujourd'hui — nested
            // sous Connect\Show, lui-même gardé), mais un tiers ne doit jamais voir le
            // contenu échangé, même masqué (audit sécurité V1) : mount() ne suffit pas à
            // protéger le contenu si ce composant est un jour exposé autrement.
            ->assertDontSee('Message confidentiel entre les deux parties.')
            ->set('body', 'Message indésirable.')
            ->call('send')
            ->assertForbidden();

        $this->assertSame(1, ConversationMessage::count());
    }

    public function test_admin_can_view_but_never_send_a_message(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Message légitime.')
            ->call('send');

        // L'admin peut ouvrir/voir la conversation (modération, lecture seule)…
        Livewire::actingAs($admin)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->assertSee('Message légitime.')
            ->assertSet('canSend', false)
            // … mais ne peut jamais y écrire, même en forçant l'appel.
            ->set('body', 'Un admin ne devrait jamais pouvoir écrire ici.')
            ->call('send')
            ->assertForbidden();

        $this->assertSame(1, ConversationMessage::count());
    }

    /* ------------------------------------------------------------------ *
     |  Détection & masquage des coordonnées (§16)
     * ------------------------------------------------------------------ */

    public function test_a_phone_number_is_stored_raw_but_masked_at_display(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Appelez-moi directement au 07 00 00 00 00.')
            ->call('send')
            ->assertHasNoErrors();

        $message = ConversationMessage::firstOrFail();
        $this->assertTrue($message->contains_flagged_content);
        $this->assertArrayHasKey('telephone', $message->flagged_patterns);
        $this->assertSame('Appelez-moi directement au 07 00 00 00 00.', $message->body);
        $this->assertStringNotContainsString('07 00 00 00 00', $message->displayBody());
        $this->assertStringContainsString('[coordonnée masquée]', $message->displayBody());

        // Les deux parties voient la version masquée, jamais le numéro en clair.
        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->assertDontSee('07 00 00 00 00')
            ->assertSee('[coordonnée masquée]');
    }

    public function test_a_plain_message_is_never_flagged(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Merci, je confirme la commande de 10 kg.')
            ->call('send');

        $message = ConversationMessage::firstOrFail();
        $this->assertFalse($message->contains_flagged_content);
        $this->assertNull($message->flagged_patterns);
        $this->assertSame($message->body, $message->displayBody());
    }

    public function test_a_flagged_message_is_never_blocked_only_masked(): void
    {
        // §16 « Important » : aide au tri, jamais un blocage absolu.
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Mon email est jean@example.com si besoin.')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame(1, ConversationMessage::count());
    }

    public function test_messages_are_never_rendered_as_raw_html(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', '<script>alert(1)</script>')
            ->call('send')
            ->assertSee('<script>alert(1)</script>'); // échappé par défaut par assertSee()
    }

    /* ------------------------------------------------------------------ *
     |  Compteur de tentatives & seuil de modération
     * ------------------------------------------------------------------ */

    public function test_flagged_messages_count_accumulates_on_the_conversation(): void
    {
        foreach (['Appelez le 07 00 00 00 00', 'Écrivez à jean@example.com', 'Bonjour, rien à signaler ici.'] as $body) {
            Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
                ->set('body', $body)
                ->call('send');
        }

        $conversation = Conversation::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();
        $this->assertSame(2, $conversation->flaggedMessagesCount());
    }

    public function test_moderation_threshold_is_not_reached_just_below_it(): void
    {
        $conversation = Conversation::create(['connection_request_id' => $this->connectionRequest->id]);

        for ($i = 0; $i < Conversation::FLAG_THRESHOLD - 1; $i++) {
            $conversation->messages()->create([
                'sender_id' => $this->buyerUser->id, 'body' => "Appelez le 070000000{$i}",
                'contains_flagged_content' => true, 'flagged_patterns' => ['telephone' => ["070000000{$i}"]],
            ]);
        }

        $this->assertFalse($conversation->needsModeration());
    }

    public function test_moderation_threshold_triggers_the_admin_badge(): void
    {
        $conversation = Conversation::create(['connection_request_id' => $this->connectionRequest->id]);

        for ($i = 0; $i < Conversation::FLAG_THRESHOLD; $i++) {
            $conversation->messages()->create([
                'sender_id' => $this->buyerUser->id, 'body' => "Appelez le 070000000{$i}",
                'contains_flagged_content' => true, 'flagged_patterns' => ['telephone' => ["070000000{$i}"]],
            ]);
        }

        $this->assertTrue($conversation->fresh()->needsModeration());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Conversations signalées');
    }

    public function test_admin_conversations_screen_lists_flagged_conversations_with_unmasked_content(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Appelez-moi au 07 00 00 00 00.')
            ->call('send');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(AdminConversations::class)
            ->assertSee('Ferme Kouassi');

        Livewire::actingAs($admin)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->assertSee('07 00 00 00 00'); // non masqué pour la modération admin
    }

    public function test_conversations_with_no_flagged_message_do_not_appear_in_admin_moderation_list(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $this->connectionRequest])
            ->set('body', 'Un message tout à fait ordinaire.')
            ->call('send');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(AdminConversations::class)
            ->assertSee('Aucune conversation signalée');
    }
}
