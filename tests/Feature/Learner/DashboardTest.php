<?php

namespace Tests\Feature\Learner;

use App\Actions\CreateConnectionRequest;
use App\Actions\SendConversationMessage;
use App\Livewire\Learner\Dashboard;
use App\Models\BuyerNeed;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use App\Notifications\ConversationMessageSentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tableau de bord UNIQUE de l'espace apprenant (Phase 15 — audit UX). Remplace les
 * anciens tests dédiés `Producer\DashboardTest`/`Buyer\DashboardTest` (composants
 * supprimés, fusionnés ici) : un compte voit toujours "Mon espace" pour ses formations,
 * et — en plus, sur la MÊME page — une section Espace producteur si `isProducer()`
 * et/ou une section Espace acheteur si `isBuyer()`, jamais un second écran "Tableau de
 * bord" séparé. Le bloc d'accroche "Devenir producteur/acheteur" reste couvert par
 * DashboardProducerBuyerCtaTest (inchangé par cette phase).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProducer(): User
    {
        $user = User::factory()->create();
        ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa, Haut-Sassandra', 'activity_type' => 'recolte',
        ]);

        return $user;
    }

    protected function makeBuyer(): User
    {
        $user = User::factory()->create();
        BuyerProfile::create(['user_id' => $user->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        return $user;
    }

    public function test_a_pure_learner_sees_neither_the_producer_nor_the_buyer_section(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertDontSee('Espace producteur')
            ->assertDontSee('Espace acheteur');
    }

    public function test_a_producer_sees_their_own_metrics_in_the_unified_dashboard(): void
    {
        $user = $this->makeProducer();
        CropOffer::create([
            'producer_profile_id' => $user->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee', 'is_available' => true,
        ]);
        CropOffer::create([
            'producer_profile_id' => $user->producerProfile->id,
            'product_name' => 'Manioc en brouillon', 'quantity' => 5, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'brouillon',
        ]);

        $buyer = User::factory()->create();
        BuyerProfile::create(['user_id' => $buyer->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);
        $offer = CropOffer::where('status', 'publiee')->firstOrFail();
        app(CreateConnectionRequest::class)->handle($buyer, 'acheteur', $offer);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Espace producteur — Ferme Kouassi')
            ->assertDontSee('Espace acheteur');

        $this->assertSame(1, $user->producerProfile->cropOffers()->where('status', 'publiee')->count());
        $this->assertSame(2, $user->producerProfile->cropOffers()->count());
        $this->assertSame(1, $user->producerProfile->connectionRequests()->count());
    }

    /**
     * Sans cette liste, il fallait quitter le tableau de bord et aller sur « Mes demandes »
     * pour retrouver ce qui est en cours (audit UX). Vérifie l'affichage ET l'exclusion
     * d'une demande devenue terminale (refusée).
     */
    public function test_the_producer_sees_active_negotiations_on_the_dashboard_but_not_refused_ones(): void
    {
        $user = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $user->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee', 'is_available' => true,
        ]);
        $buyer = User::factory()->create(['name' => 'Awa Koné']);
        BuyerProfile::create(['user_id' => $buyer->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);
        $cr = app(CreateConnectionRequest::class)->handle($buyer, 'acheteur', $offer);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('En cours')
            ->assertSee('Manioc frais')
            ->assertSee('Awa Koné')
            ->assertSee(route('learner.requests.show', $cr), false);

        $cr->refuse($user);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertDontSee('En cours');
    }

    public function test_a_producer_only_sees_their_own_offers_in_the_dashboard(): void
    {
        $owner = $this->makeProducer();
        $other = $this->makeProducer();
        CropOffer::create([
            'producer_profile_id' => $other->producerProfile->id,
            'product_name' => 'Offre concurrente', 'quantity' => 5, 'unit' => 'sac',
            'location' => 'Bouaké', 'status' => 'publiee', 'is_available' => true,
        ]);

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertDontSee('Offre concurrente');
    }

    public function test_a_buyer_sees_their_own_metrics_and_recent_producer_offers(): void
    {
        $user = $this->makeBuyer();
        BuyerNeed::create([
            'buyer_profile_id' => $user->buyerProfile->id,
            'product_wanted' => 'Manioc frais', 'quantity' => 10, 'unit' => 'tonne',
            'location' => 'Yamoussoukro', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);

        $producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);
        CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Brouillon jamais publié',
            'quantity' => 5, 'unit' => 'kg', 'location' => 'Daloa', 'status' => 'brouillon',
        ]);
        app(CreateConnectionRequest::class)->handle($user, 'acheteur', $offer);
        $user->favoriteProducers()->attach($producerProfile->id);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Espace acheteur')
            ->assertDontSee('Espace producteur')
            ->assertSee('Manioc frais')
            ->assertSee('Ferme Kouassi')
            ->assertDontSee('Brouillon jamais publié')
            ->assertSeeHtml(route('learner.buyer.offers.contact', $offer));

        $this->assertSame(1, $user->buyerProfile->buyerNeeds()->where('status', 'ouvert')->count());
        $this->assertSame(1, $user->buyerProfile->connectionRequests()->count());
        $this->assertSame(1, $user->favoriteProducers()->count());
    }

    /** Le cœur du correctif : un compte cumulant les deux espaces voit TOUT sur une seule page. */
    public function test_a_dual_role_account_sees_both_sections_on_the_same_page(): void
    {
        $user = $this->makeProducer();
        BuyerProfile::create(['user_id' => $user->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Espace producteur — Ferme Kouassi')
            ->assertSee('Espace acheteur');
    }

    public function test_the_unified_dashboard_is_reachable_over_http_with_the_nav_link(): void
    {
        $user = $this->makeProducer();
        BuyerProfile::create(['user_id' => $user->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $html = $this->actingAs($user)->get(route('learner.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Espace producteur', $html);
        $this->assertStringContainsString('Espace acheteur', $html);
        // Un seul lien de menu mène à un tableau de bord ("Mon espace" ci-dessus, devenu
        // "Tableau de bord") — les anciens libellés qualifiés ("... producteur"/"...
        // acheteur") n'existent plus du tout, ils désignaient les écrans supprimés.
        $this->assertStringNotContainsString('Tableau de bord producteur', $html);
        $this->assertStringNotContainsString('Tableau de bord acheteur', $html);
        $this->assertSame(1, substr_count($html, 'class="nav-item on"'));
    }

    /** Les anciennes routes dédiées ont été retirées — tout se passe désormais sur /mon-espace. */
    public function test_the_standalone_producer_and_buyer_dashboard_routes_no_longer_exist(): void
    {
        $this->assertFalse(Route::has('learner.producer.dashboard'));
        $this->assertFalse(Route::has('learner.buyer.dashboard'));
    }

    /* ------------------------------------------------------------------ *
     |  Messages non lus mis en avant (demande explicite : "attirer
     |  l'attention une fois connecté dans son tableau de bord")
     * ------------------------------------------------------------------ */

    protected function createRequestAndSendMessage(): array
    {
        $producerUser = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $producerUser->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee', 'is_available' => true,
        ]);
        $buyerUser = $this->makeBuyer();
        $connectionRequest = app(CreateConnectionRequest::class)->handle($buyerUser, 'acheteur', $offer);

        app(SendConversationMessage::class)->handle($buyerUser, $connectionRequest, 'Bonjour, votre offre m\'intéresse.');

        return [$producerUser, $buyerUser, $connectionRequest];
    }

    public function test_a_new_message_is_shown_prominently_on_the_recipients_dashboard(): void
    {
        [$producerUser, $buyerUser] = $this->createRequestAndSendMessage();

        Livewire::actingAs($producerUser)->test(Dashboard::class)
            ->assertSee('1 nouveau message')
            // Le corps du message n'apparaît volontairement pas dans la notification
            // (cf. ConversationMessageSentNotification) — seul un résumé générique
            // (expéditeur + objet de la demande) est affiché.
            ->assertSee($buyerUser->name)
            ->assertSee('Manioc frais');
    }

    /** L'expéditeur ne voit jamais son propre message comme "nouveau" chez lui. */
    public function test_the_sender_does_not_see_their_own_message_as_unread(): void
    {
        [, $buyerUser] = $this->createRequestAndSendMessage();

        Livewire::actingAs($buyerUser)->test(Dashboard::class)
            ->assertDontSee('nouveau message');
    }

    public function test_marking_a_single_message_as_read_removes_it_from_the_banner(): void
    {
        [$producerUser] = $this->createRequestAndSendMessage();
        $notification = $producerUser->unreadNotifications()
            ->where('type', ConversationMessageSentNotification::class)->firstOrFail();

        Livewire::actingAs($producerUser)->test(Dashboard::class)
            ->call('markMessageRead', $notification->id)
            ->assertDontSee('nouveau message');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_messages_read_clears_the_banner_without_touching_other_notifications(): void
    {
        [$producerUser, $buyerUser, $connectionRequest] = $this->createRequestAndSendMessage();
        $connectionRequest->accept($producerUser); // notification "demande acceptée" pour le demandeur (buyer), sans rapport ici

        Livewire::actingAs($producerUser)->test(Dashboard::class)
            ->call('markAllMessagesRead')
            ->assertDontSee('nouveau message');

        $this->assertSame(0, $producerUser->unreadNotifications()
            ->where('type', ConversationMessageSentNotification::class)->count());
    }

    /** Les autres notifications (hors message) restent discrètes — pas de bannière dédiée. */
    public function test_non_message_notifications_do_not_trigger_the_prominent_banner(): void
    {
        $producerUser = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $producerUser->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee', 'is_available' => true,
        ]);
        $buyerUser = $this->makeBuyer();
        app(CreateConnectionRequest::class)->handle($buyerUser, 'acheteur', $offer);
        // "Nouvelle demande" notifie le producteur — aucun message envoyé ici.

        Livewire::actingAs($producerUser)->test(Dashboard::class)
            ->assertDontSee('nouveau message')
            ->assertSee('autre(s) notification(s)');
    }
}
