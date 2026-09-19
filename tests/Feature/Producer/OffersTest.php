<?php

namespace Tests\Feature\Producer;

use App\Livewire\Producer\OfferForm;
use App\Livewire\Producer\Offers;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OffersTest extends TestCase
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

    public function test_producer_creates_an_offer_and_is_redirected_to_the_edit_page(): void
    {
        $user = $this->makeProducer();

        Livewire::actingAs($user)->test(OfferForm::class)
            ->set('product_name', 'Manioc frais')
            ->set('quantity', '12.5')
            ->set('unit', 'kg')
            ->set('price_indicative', 150)
            ->set('location', 'Daloa')
            ->set('status', 'publiee')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $offer = CropOffer::firstOrFail();
        $this->assertSame('Manioc frais', $offer->product_name);
        $this->assertSame('kg', $offer->unit->value);
        $this->assertSame('publiee', $offer->status->value);
        $this->assertSame($user->producerProfile->id, $offer->producer_profile_id);
    }

    public function test_quantity_must_be_positive_and_price_must_be_a_positive_integer_or_null(): void
    {
        $user = $this->makeProducer();

        Livewire::actingAs($user)->test(OfferForm::class)
            ->set('product_name', 'Manioc frais')
            ->set('quantity', '0')
            ->set('location', 'Daloa')
            ->call('save')
            ->assertHasErrors(['quantity']);

        Livewire::actingAs($user)->test(OfferForm::class)
            ->set('product_name', 'Manioc frais')
            ->set('quantity', '10')
            ->set('price_indicative', -5)
            ->set('location', 'Daloa')
            ->call('save')
            ->assertHasErrors(['price_indicative']);

        Livewire::actingAs($user)->test(OfferForm::class)
            ->set('product_name', 'Manioc frais')
            ->set('quantity', '10')
            ->set('price_indicative', null)
            ->set('location', 'Daloa')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_producer_uploads_photos_up_to_five(): void
    {
        Storage::fake('public');
        $user = $this->makeProducer();

        Livewire::actingAs($user)->test(OfferForm::class)
            ->set('product_name', 'Manioc frais')
            ->set('quantity', '10')
            ->set('location', 'Daloa')
            ->set('newPhotos', [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ])
            ->call('save')
            ->assertHasNoErrors();

        $offer = CropOffer::firstOrFail();
        $this->assertCount(2, $offer->photos);
        Storage::disk('public')->assertExists($offer->photos->first()->path);
    }

    public function test_more_than_five_photos_are_rejected(): void
    {
        Storage::fake('public');
        $user = $this->makeProducer();

        Livewire::actingAs($user)->test(OfferForm::class)
            ->set('product_name', 'Manioc frais')
            ->set('quantity', '10')
            ->set('location', 'Daloa')
            ->set('newPhotos', [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
                UploadedFile::fake()->image('d.jpg'),
                UploadedFile::fake()->image('e.jpg'),
                UploadedFile::fake()->image('f.jpg'),
            ])
            ->call('save')
            ->assertHasErrors(['newPhotos']);

        $this->assertSame(0, CropOffer::count());
    }

    public function test_producer_edits_their_offer_and_removes_a_photo(): void
    {
        Storage::fake('public');
        $user = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $user->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'brouillon', 'is_available' => true,
        ]);
        $photo = $offer->photos()->create([
            'path' => UploadedFile::fake()->image('a.jpg')->store('crop-offers', 'public'),
            'position' => 1,
        ]);

        Livewire::actingAs($user)->test(OfferForm::class, ['offer' => $offer])
            ->assertSet('product_name', 'Manioc frais')
            ->call('removeExistingPhoto', $photo->id)
            ->set('product_name', 'Manioc frais premium')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Manioc frais premium', $offer->fresh()->product_name);
        $this->assertCount(0, $offer->fresh()->photos);
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_a_producer_cannot_edit_another_producers_offer(): void
    {
        $owner = $this->makeProducer();
        $intruder = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $owner->producerProfile->id,
            'product_name' => 'Manioc du propriétaire', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'brouillon',
        ]);

        Livewire::actingAs($intruder)->test(OfferForm::class, ['offer' => $offer])
            ->assertForbidden();
    }

    public function test_producer_only_sees_their_own_offers_in_the_list(): void
    {
        $owner = $this->makeProducer();
        $other = $this->makeProducer();
        CropOffer::create([
            'producer_profile_id' => $owner->producerProfile->id,
            'product_name' => 'Offre du propriétaire', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee',
        ]);
        CropOffer::create([
            'producer_profile_id' => $other->producerProfile->id,
            'product_name' => 'Offre concurrente', 'quantity' => 5, 'unit' => 'sac',
            'location' => 'Bouaké', 'status' => 'publiee',
        ]);

        Livewire::actingAs($owner)->test(Offers::class)
            ->assertSee('Offre du propriétaire')
            ->assertDontSee('Offre concurrente');
    }

    public function test_producer_toggles_availability_without_changing_status(): void
    {
        $user = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $user->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee', 'is_available' => true,
        ]);

        Livewire::actingAs($user)->test(Offers::class)
            ->call('toggleAvailability', $offer->id);

        $offer->refresh();
        $this->assertFalse($offer->is_available);
        $this->assertSame('publiee', $offer->status->value);
    }

    public function test_a_producer_cannot_toggle_another_producers_offer(): void
    {
        $owner = $this->makeProducer();
        $intruder = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $owner->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee',
        ]);

        Livewire::actingAs($intruder)->test(Offers::class)
            ->call('toggleAvailability', $offer->id)
            ->assertForbidden();

        $this->assertTrue($offer->fresh()->is_available);
    }

    public function test_producer_deletes_their_offer(): void
    {
        $user = $this->makeProducer();
        $offer = CropOffer::create([
            'producer_profile_id' => $user->producerProfile->id,
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'brouillon',
        ]);

        Livewire::actingAs($user)->test(Offers::class)
            ->call('delete', $offer->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($offer);
    }

    public function test_a_learner_without_a_producer_profile_is_redirected_from_the_offers_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('learner.producer.offers'))
            ->assertRedirect(route('learner.producer'));

        $this->actingAs($user)->get(route('learner.producer.offers.create'))
            ->assertRedirect(route('learner.producer'));
    }

    /**
     * Audit UX (Phase 2) : cette redirection ne doit plus être silencieuse — un message
     * explique pourquoi l'utilisateur atterrit sur l'activation du profil producteur.
     */
    public function test_the_redirect_away_from_offers_explains_why(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('learner.producer.offers'));

        $response->assertRedirect(route('learner.producer'));
        $this->assertSame(
            "Activez d'abord votre profil producteur pour accéder à cette page.",
            $response->getSession()->get('flash')
        );
    }

    public function test_offers_nav_link_appears_once_producer_profile_exists(): void
    {
        $user = $this->makeProducer();

        $this->actingAs($user)->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Mes offres')
            ->assertSee('Profil producteur');
    }

    public function test_saving_an_offer_is_rate_limited(): void
    {
        $user = $this->makeProducer();

        for ($i = 0; $i < 6; $i++) {
            Livewire::actingAs($user)->test(OfferForm::class)
                ->set('product_name', "Manioc frais $i")
                ->set('quantity', '10')
                ->set('unit', 'kg')
                ->set('location', 'Daloa')
                ->set('status', 'brouillon')
                ->call('save')
                ->assertHasNoErrors();
        }

        Livewire::actingAs($user)->test(OfferForm::class)
            ->set('product_name', 'Une de trop')
            ->set('quantity', '10')
            ->set('unit', 'kg')
            ->set('location', 'Daloa')
            ->set('status', 'brouillon')
            ->call('save')
            ->assertHasErrors('product_name');

        $this->assertSame(6, CropOffer::count());
    }
}
