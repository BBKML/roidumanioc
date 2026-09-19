<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateConnectionRequest;
use App\Livewire\Admin\ConnectionRequests;
use App\Models\BuyerProfile;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Supervision passive — aucune action de mutation, uniquement une liste + un lien de détail. */
class ConnectConnectionRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $producerUser;

    protected User $buyerUser;

    protected ConnectionRequest $connectionRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();

        $this->producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi', 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $this->connectionRequest = app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', $offer, 'Bonjour, intéressé.');
    }

    public function test_admin_sees_every_connection_request(): void
    {
        Livewire::actingAs($this->admin)->test(ConnectionRequests::class)
            ->assertSee('Manioc frais')
            ->assertSee('Ferme Kouassi');
    }

    /** Audit UX (Phase 3) : tableau adapté en cartes sous 700px (même mécanisme). */
    public function test_the_table_is_mobile_ready(): void
    {
        Livewire::actingAs($this->admin)->test(ConnectionRequests::class)
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Producteur"', false);
    }

    public function test_filter_tab_scopes_by_status(): void
    {
        $this->connectionRequest->accept($this->producerUser);

        Livewire::actingAs($this->admin)->test(ConnectionRequests::class)
            ->set('filter', 'acceptee')
            ->assertSee('Manioc frais')
            ->set('filter', 'refusee')
            ->assertDontSee('Manioc frais');
    }

    /** Audit architecture : état vide contextualisé par statut plutôt qu'un texte générique. */
    public function test_the_empty_state_names_the_current_status_filter(): void
    {
        Livewire::actingAs($this->admin)->test(ConnectionRequests::class)
            ->set('filter', 'refusee')
            ->assertSee('Aucune demande au statut « Refusée » pour le moment.');
    }

    public function test_the_detail_link_points_to_the_shared_connection_request_screen(): void
    {
        Livewire::actingAs($this->admin)->test(ConnectionRequests::class)
            ->assertSeeHtml(route('learner.requests.show', $this->connectionRequest));
    }

    public function test_admin_can_open_the_detail_screen_read_only(): void
    {
        $this->actingAs($this->admin)
            ->get(route('learner.requests.show', $this->connectionRequest))
            ->assertOk();
    }

    /**
     * Audit UX (Phase 13) : un admin qui ouvre cet écran partagé voyait jusqu'ici le même
     * menu qu'un client (« Apprenant », « Devenir producteur/acheteur ») — corrigé par un
     * bandeau explicite + un menu adapté à son rôle.
     */
    public function test_the_admin_sees_a_read_only_banner_and_no_customer_upsell(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('learner.requests.show', $this->connectionRequest))
            ->assertOk();

        $response->assertSee('Vue administrateur — lecture seule.')
            ->assertSee(route('admin.connection-requests'), false)
            ->assertSee('Administrateur')
            ->assertDontSee('Devenir producteur')
            ->assertDontSee('Devenir acheteur');
    }

    public function test_a_learner_who_is_not_a_party_cannot_access_the_admin_screen(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.connection-requests'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.connection-requests'))->assertRedirect(route('login'));
    }
}
