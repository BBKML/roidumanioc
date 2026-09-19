<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateConnectionRequest;
use App\Enums\CollaborationDeliveryStatus;
use App\Livewire\Admin\Reviews as AdminReviews;
use App\Livewire\Connect\Show;
use App\Livewire\Public\Producers;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Modération des avis (Phase 12, §25 — jusque-là seulement un compteur en lecture seule sur
 * Admin\ProducerShow). Ne doit jamais permettre d'éditer/supprimer le contenu d'un avis
 * (immuable) — seulement de le masquer/réafficher, cf. Review::hide()/unhide().
 */
class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'actif']);

        $this->producerUser = User::factory()->create();
        $this->producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $this->producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $connectionRequest = app(CreateConnectionRequest::class)->handle($buyerUser, 'acheteur', $offer);
        $connectionRequest->accept($this->producerUser);
        $connectionRequest->moveToNegotiation($buyerUser);
        $connectionRequest->propose($this->producerUser);

        Livewire::actingAs($this->producerUser)->test(Show::class, ['connectionRequest' => $connectionRequest])
            ->call('confirmCollaboration');

        $collaboration = Collaboration::where('connection_request_id', $connectionRequest->id)->firstOrFail();
        $collaboration->declarePayment($buyerUser, 100000, 'Espèces');
        $collaboration->confirmPayment($this->producerUser);
        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours);
        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::Effectuee);
        $collaboration->markDeliveryStep($buyerUser, CollaborationDeliveryStatus::Receptionnee);

        $this->review = Review::create([
            'collaboration_id' => $collaboration->fresh()->id,
            'rater_id' => $buyerUser->id,
            'ratee_id' => $this->producerUser->id,
            'direction' => 'acheteur_vers_producteur',
            'rating' => 1,
            'criteria' => ['qualite' => 1, 'quantite' => 1, 'respect_engagements' => 1, 'ponctualite' => 1, 'communication' => 1],
            'comment' => 'Avis injurieux à modérer.',
        ]);
    }

    public function test_a_non_admin_cannot_access_the_reviews_screen(): void
    {
        $this->actingAs($this->producerUser)->get(route('admin.reviews'))->assertForbidden();
    }

    public function test_the_list_shows_reviews_with_their_status(): void
    {
        Livewire::actingAs($this->admin)->test(AdminReviews::class)
            ->assertSee('Avis injurieux à modérer')
            ->assertSee($this->producerUser->name);
    }

    public function test_hiding_a_review_requires_a_reason(): void
    {
        Livewire::actingAs($this->admin)->test(AdminReviews::class)
            ->call('startHiding', $this->review->id)
            ->call('hide')
            ->assertHasErrors(['hideReason']);

        $this->assertFalse($this->review->fresh()->isHidden());
    }

    public function test_admin_hides_and_unhides_a_review_without_altering_its_content(): void
    {
        Livewire::actingAs($this->admin)->test(AdminReviews::class)
            ->call('startHiding', $this->review->id)
            ->set('hideReason', 'Contenu injurieux signalé par le producteur.')
            ->call('hide')
            ->assertHasNoErrors();

        $this->review->refresh();
        $this->assertTrue($this->review->isHidden());
        $this->assertSame($this->admin->id, $this->review->hidden_by);
        $this->assertSame('Contenu injurieux signalé par le producteur.', $this->review->hidden_reason);
        // Le contenu déposé n'est jamais touché.
        $this->assertSame(1, $this->review->rating);
        $this->assertSame('Avis injurieux à modérer.', $this->review->comment);

        Livewire::actingAs($this->admin)->test(AdminReviews::class)
            ->call('unhide', $this->review->id);

        $this->assertFalse($this->review->fresh()->isHidden());
    }

    public function test_hiding_is_idempotent(): void
    {
        $this->review->hide($this->admin, 'Premier motif');

        $this->assertFalse($this->review->hide($this->admin, 'Second motif'));
        $this->assertSame('Premier motif', $this->review->fresh()->hidden_reason);
    }

    public function test_a_hidden_review_is_excluded_from_the_producers_public_average_and_count(): void
    {
        $this->assertSame(1, $this->producerProfile->reviewsCount());

        $this->review->hide($this->admin, 'Motif');

        $this->assertSame(0, $this->producerProfile->reviewsCount());
        $this->assertNull($this->producerProfile->averageRating());

        Livewire::test(Producers::class)
            ->assertSee("Pas encore d'évaluation", false);
    }
}
