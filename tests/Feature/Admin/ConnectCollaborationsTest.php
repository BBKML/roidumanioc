<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateConnectionRequest;
use App\Livewire\Admin\Collaborations;
use App\Livewire\Connect\Show as ConnectShow;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class ConnectCollaborationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $producerUser;

    protected User $buyerUser;

    protected ConnectionRequest $connectionRequest;

    protected Collaboration $collaboration;

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
            'status' => 'publiee', 'is_available' => true, 'price_indicative' => 50000,
        ]);

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $this->connectionRequest = app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $offer);
        $this->connectionRequest->accept($this->producerUser);
        $this->connectionRequest->moveToNegotiation($this->buyerUser);
        $this->connectionRequest->propose($this->producerUser);

        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('confirmCollaboration');

        $this->collaboration = Collaboration::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();

        // La construction de la fixture ci-dessus authentifie temporairement le producteur
        // (via Livewire::actingAs) — on déconnecte pour ne pas fausser les tests "invité".
        Auth::logout();
    }

    public function test_default_tab_shows_only_disputed_collaborations(): void
    {
        Livewire::actingAs($this->admin)->test(Collaborations::class)
            ->assertDontSee('Ferme Kouassi');

        $this->collaboration->markDisputed($this->admin, 'Signalement test');

        Livewire::actingAs($this->admin)->test(Collaborations::class)
            ->assertSee('Ferme Kouassi');
    }

    /** Audit architecture : état vide contextualisé par statut plutôt qu'un texte générique. */
    public function test_the_empty_state_names_the_current_status_filter(): void
    {
        Livewire::actingAs($this->admin)->test(Collaborations::class)
            ->set('filter', 'annulee')
            ->assertSee('Aucune collaboration au statut « Annulée » pour le moment.');
    }

    public function test_tous_tab_shows_every_collaboration(): void
    {
        Livewire::actingAs($this->admin)->test(Collaborations::class)
            ->set('filter', 'tous')
            ->assertSee('Ferme Kouassi');
    }

    /** Audit UX (Phase 3) : tableau adapté en cartes sous 700px (même mécanisme). */
    public function test_the_table_is_mobile_ready(): void
    {
        Livewire::actingAs($this->admin)->test(Collaborations::class)
            ->set('filter', 'tous')
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Producteur"', false);
    }

    public function test_estimated_value_falls_back_to_the_offers_indicative_price(): void
    {
        $this->assertSame(50000, $this->collaboration->estimatedValue());
    }

    /**
     * Audit UX (Phase 13) : un admin qui ouvre « Ma collaboration » (écran partagé, lien
     * « Voir » depuis cette liste) voyait jusqu'ici le même menu qu'un client — corrigé
     * par un bandeau explicite + un menu adapté à son rôle.
     */
    public function test_the_admin_sees_a_read_only_banner_and_no_customer_upsell(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('learner.collaborations.show', $this->collaboration))
            ->assertOk();

        $response->assertSee('Vue administrateur — lecture seule.')
            ->assertSee(route('admin.collaborations'), false)
            ->assertSee('Administrateur')
            ->assertDontSee('Devenir producteur')
            ->assertDontSee('Devenir acheteur');
    }

    public function test_admin_resolves_a_dispute(): void
    {
        $this->collaboration->markDisputed($this->admin, 'Signalement test');

        Livewire::actingAs($this->admin)->test(Collaborations::class)
            ->call('startResolving', $this->collaboration->id)
            ->set('resolution', 'Les parties se sont entendues au téléphone.')
            ->call('resolveDispute');

        $fresh = $this->collaboration->fresh();
        $this->assertSame('en_cours', $fresh->status->value);
    }

    public function test_resolving_requires_a_resolution_note(): void
    {
        $this->collaboration->markDisputed($this->admin, 'Signalement test');

        Livewire::actingAs($this->admin)->test(Collaborations::class)
            ->call('startResolving', $this->collaboration->id)
            ->set('resolution', '')
            ->call('resolveDispute')
            ->assertHasErrors('resolution');

        $this->assertSame('litige', $this->collaboration->fresh()->status->value);
    }

    public function test_resolving_a_collaboration_not_in_dispute_is_a_no_op(): void
    {
        $this->assertFalse($this->collaboration->resolveDispute($this->admin, 'test'));
        $this->assertSame('en_cours', $this->collaboration->fresh()->status->value);
    }

    public function test_resolution_is_logged(): void
    {
        $this->collaboration->markDisputed($this->admin, 'Signalement test');
        $this->collaboration->resolveDispute($this->admin, 'Accord trouvé');

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Collaboration::class,
            'subject_id' => $this->collaboration->id,
            'causer_id' => $this->admin->id,
            'description' => 'Litige résolu : Accord trouvé',
        ]);
    }

    public function test_a_party_cannot_resolve_a_dispute(): void
    {
        $this->collaboration->markDisputed($this->admin, 'Signalement test');

        $this->assertFalse($this->collaboration->resolveDispute($this->producerUser, 'test'));
        $this->assertSame('litige', $this->collaboration->fresh()->status->value);
    }

    public function test_a_learner_cannot_access_the_collaborations_screen(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.collaborations'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.collaborations'))->assertRedirect(route('login'));
    }
}
