<?php

namespace Tests\Feature\Connect;

use App\Actions\CreateConnectionRequest;
use App\Enums\CollaborationDeliveryStatus;
use App\Livewire\Connect\ReviewForm;
use App\Livewire\Connect\Show as ConnectShow;
use App\Livewire\Public\Producers;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected User $buyerUser;

    protected ConnectionRequest $connectionRequest;

    protected Collaboration $collaboration;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $this->connectionRequest = app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $offer);
        $this->connectionRequest->accept($this->producerUser);
        $this->connectionRequest->moveToNegotiation($this->buyerUser);
        $this->connectionRequest->propose($this->producerUser);

        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('confirmCollaboration');

        $this->collaboration = Collaboration::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();
    }

    protected function completeCollaboration(): void
    {
        $this->collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');
        $this->collaboration->confirmPayment($this->producerUser);
        $this->collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours);
        $this->collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::Effectuee);
        $this->collaboration->markDeliveryStep($this->buyerUser, CollaborationDeliveryStatus::Receptionnee);
        $this->collaboration->refresh();
    }

    /* ------------------------------------------------------------------ *
     |  Création
     * ------------------------------------------------------------------ */

    public function test_a_review_cannot_be_submitted_before_the_collaboration_is_terminee(): void
    {
        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    public function test_buyer_reviews_the_producer_with_the_correct_direction_and_criteria(): void
    {
        $this->completeCollaboration();

        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 5)
            ->set('criteria.qualite', 5)
            ->set('criteria.quantite', 4)
            ->set('criteria.respect_engagements', 5)
            ->set('criteria.ponctualite', 4)
            ->set('criteria.communication', 5)
            ->set('comment', 'Très bon producteur, marchandise conforme.')
            ->call('submit')
            ->assertHasNoErrors();

        $review = Review::firstOrFail();
        $this->assertSame('acheteur_vers_producteur', $review->direction->value);
        $this->assertSame($this->buyerUser->id, $review->rater_id);
        $this->assertSame($this->producerUser->id, $review->ratee_id);
        $this->assertSame(5, $review->rating);
        $this->assertSame(
            ['qualite' => 5, 'quantite' => 4, 'respect_engagements' => 5, 'ponctualite' => 4, 'communication' => 5],
            $review->criteria,
        );
    }

    public function test_producer_reviews_the_buyer_with_the_correct_direction_and_criteria(): void
    {
        $this->completeCollaboration();

        Livewire::actingAs($this->producerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 4)
            ->set('criteria.respect_engagements', 4)
            ->set('criteria.paiement', 5)
            ->set('criteria.communication', 4)
            ->set('criteria.ponctualite', 3)
            ->call('submit')
            ->assertHasNoErrors();

        $review = Review::firstOrFail();
        $this->assertSame('producteur_vers_acheteur', $review->direction->value);
        $this->assertSame($this->producerUser->id, $review->rater_id);
        $this->assertSame($this->buyerUser->id, $review->ratee_id);
        $this->assertArrayNotHasKey('qualite', $review->criteria); // pas un critère acheteur→producteur
    }

    public function test_both_parties_can_review_the_same_collaboration_independently(): void
    {
        $this->completeCollaboration();

        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 5)
            ->set('criteria.qualite', 5)->set('criteria.quantite', 5)->set('criteria.respect_engagements', 5)
            ->set('criteria.ponctualite', 5)->set('criteria.communication', 5)
            ->call('submit');

        Livewire::actingAs($this->producerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 5)
            ->set('criteria.respect_engagements', 5)->set('criteria.paiement', 5)
            ->set('criteria.communication', 5)->set('criteria.ponctualite', 5)
            ->call('submit');

        $this->assertSame(2, Review::count());
    }

    public function test_a_party_cannot_review_the_same_collaboration_twice(): void
    {
        $this->completeCollaboration();
        $reviewData = fn () => Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 5)
            ->set('criteria.qualite', 5)->set('criteria.quantite', 5)->set('criteria.respect_engagements', 5)
            ->set('criteria.ponctualite', 5)->set('criteria.communication', 5);

        $reviewData()->call('submit')->assertHasNoErrors();
        $reviewData()->call('submit')->assertForbidden();

        $this->assertSame(1, Review::count());
    }

    public function test_a_third_party_cannot_submit_a_review(): void
    {
        $this->completeCollaboration();
        $intruder = User::factory()->create();

        Livewire::actingAs($intruder)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    public function test_admin_can_never_submit_a_review(): void
    {
        $this->completeCollaboration();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->assertSet('canReview', false)
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    /* ------------------------------------------------------------------ *
     |  Moyenne / compteur du producteur
     * ------------------------------------------------------------------ */

    public function test_producer_average_rating_and_count_update_after_a_buyer_review(): void
    {
        $this->completeCollaboration();
        $this->assertNull($this->producerProfile->averageRating());
        $this->assertSame(0, $this->producerProfile->reviewsCount());

        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 4)
            ->set('criteria.qualite', 4)->set('criteria.quantite', 4)->set('criteria.respect_engagements', 4)
            ->set('criteria.ponctualite', 4)->set('criteria.communication', 4)
            ->call('submit');

        $fresh = $this->producerProfile->fresh();
        $this->assertSame(4.0, $fresh->averageRating());
        $this->assertSame(1, $fresh->reviewsCount());
    }

    public function test_a_producers_review_of_the_buyer_does_not_affect_the_producers_own_average(): void
    {
        $this->completeCollaboration();

        Livewire::actingAs($this->producerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 5)
            ->set('criteria.respect_engagements', 5)->set('criteria.paiement', 5)
            ->set('criteria.communication', 5)->set('criteria.ponctualite', 5)
            ->call('submit');

        $this->assertNull($this->producerProfile->fresh()->averageRating());
        $this->assertSame(0, $this->producerProfile->fresh()->reviewsCount());
    }

    public function test_public_catalog_shows_the_average_rating_and_filters_by_it(): void
    {
        $this->completeCollaboration();
        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 5)
            ->set('criteria.qualite', 5)->set('criteria.quantite', 5)->set('criteria.respect_engagements', 5)
            ->set('criteria.ponctualite', 5)->set('criteria.communication', 5)
            ->call('submit');

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertSee('5,0/5 (1)');

        Livewire::test(Producers::class)
            ->set('minRating', '4')
            ->assertSee('Ferme Kouassi')
            ->set('minRating', '') // reset avant le seuil trop haut pour éviter tout état résiduel
            ->set('minRating', '5')
            ->assertSee('Ferme Kouassi');
    }

    /* ------------------------------------------------------------------ *
     |  Confidentialité
     * ------------------------------------------------------------------ */

    public function test_no_personal_contact_information_is_exposed_in_the_review_screens(): void
    {
        $this->producerUser->update(['phone' => '0700000033', 'email' => 'secret-producer@example.com']);
        $this->buyerUser->update(['phone' => '0700000022', 'email' => 'secret-buyer@example.com']);
        $this->completeCollaboration();

        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->assertDontSee('0700000033')
            ->assertDontSee('secret-producer@example.com');
    }

    /* ------------------------------------------------------------------ *
     |  Sécurité
     * ------------------------------------------------------------------ */

    public function test_submitting_a_review_is_rate_limited(): void
    {
        $this->completeCollaboration();

        // La contrainte d'unicité (collaboration, auteur) empêche déjà 10 vraies
        // soumissions sur la même collaboration — on simule directement l'épuisement du
        // quota pour exercer le garde-fou sans construire 10 collaborations terminées.
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit('submit-review:'.$this->buyerUser->id, 3600);
        }

        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $this->collaboration])
            ->set('rating', 5)
            ->set('criteria.qualite', 5)->set('criteria.quantite', 5)->set('criteria.respect_engagements', 5)
            ->set('criteria.ponctualite', 5)->set('criteria.communication', 5)
            ->call('submit')
            ->assertHasErrors('rating');

        $this->assertSame(0, Review::count());
    }
}
