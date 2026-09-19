<?php

namespace Tests\Feature;

use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_page_renders_content_from_database(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee("L'or des visionnaires")
            ->assertSee('matière <em>royale</em>', false)          // titre héros (HTML autorisé)
            ->assertSee('Faire du manioc une filière de fierté et de revenus.'); // section mission
    }

    public function test_home_lists_only_published_formations(): void
    {
        $this->get('/')
            ->assertSee('Réussir la culture du manioc')      // publiée
            ->assertDontSee('Gestion et commercialisation');  // brouillon
    }

    public function test_home_shows_marketplace_and_community_from_database(): void
    {
        $this->get('/')
            ->assertSee('Ahou S.')                  // témoignage
            ->assertSee("Éléphant d'Or")            // distinction (apostrophe échappée par Blade)
            ->assertSee('Seggo Kobenan Michel');    // fondateur
    }

    public function test_home_head_carries_dynamic_seo_and_open_graph(): void
    {
        $this->get('/')
            ->assertSee('<meta property="og:title" content="Le Roi du Manioc"', false)
            ->assertSee('<meta property="og:image"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('<title>Le Roi du Manioc</title>', false);
    }

    public function test_sitemap_is_valid_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<urlset', false)
            ->assertSee(route('contact'), false);
    }

    /* ------------------------------------------------------------------ *
     |  Indicateur communauté (§8.1) — tiré des vraies données
     * ------------------------------------------------------------------ */

    public function test_home_shows_the_live_community_indicator_when_data_exists(): void
    {
        $producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $producerProfile->verify(User::factory()->admin()->create());

        $buyerUser = User::factory()->create();
        $buyerProfile = BuyerProfile::create([
            'user_id' => $buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);
        $connectionRequest = ConnectionRequest::create([
            'requester_user_id' => $buyerUser->id, 'requester_role' => 'acheteur',
            'producer_profile_id' => $producerProfile->id, 'buyer_profile_id' => $buyerProfile->id,
            'crop_offer_id' => CropOffer::create([
                'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
                'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
                'status' => 'publiee', 'is_available' => true,
            ])->id,
        ]);
        Collaboration::create([
            'connection_request_id' => $connectionRequest->id,
            'producer_profile_id' => $producerProfile->id, 'buyer_profile_id' => $buyerProfile->id,
            'agreed_product' => 'Manioc frais', 'agreed_quantity' => 10, 'agreed_unit' => 'kg',
            'status' => 'terminee',
        ]);

        $this->get('/')
            ->assertSee('1</b> producteur vérifié', false)
            ->assertSee('1</b> collaboration terminée', false);
    }

    public function test_home_hides_the_community_indicator_when_there_is_no_real_data_yet(): void
    {
        $this->get('/')->assertDontSee('producteur vérifié');
    }
}
