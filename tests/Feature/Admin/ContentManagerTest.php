<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ContentManager;
use App\Models\Partner;
use App\Models\SiteContent;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ContentManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
    }

    public function test_editing_a_section_updates_the_database_and_the_public_site(): void
    {
        Livewire::test(ContentManager::class)
            ->set('data.hero.title_html.fr', 'Le manioc, <em>test</em>.')
            ->set('data.hero.eyebrow.fr', 'Sur-titre modifié')
            ->call('save', 'hero')
            ->assertHasNoErrors();

        $this->assertSame('Sur-titre modifié', SiteContent::where('key', 'hero')->value('data')['eyebrow']['fr']);

        // Le cache "forever" a été vidé : la vitrine reflète la nouvelle valeur (repli FR par défaut).
        $this->get('/')->assertSee('Le manioc, <em>test</em>.', false);
    }

    public function test_list_fields_round_trip_as_newline_text(): void
    {
        Livewire::test(ContentManager::class)
            ->set('data.bandeau.items.fr', "Prix A\nPrix B\n\nPrix C")
            ->call('save', 'bandeau')
            ->assertHasNoErrors();

        $items = SiteContent::where('key', 'bandeau')->value('data')['items'];
        $this->assertSame(['Prix A', 'Prix B', 'Prix C'], $items['fr']);
    }

    public function test_english_translation_is_shown_when_filled_and_french_used_as_fallback(): void
    {
        Livewire::test(ContentManager::class)
            ->set('data.hero.eyebrow.fr', 'Royaume du manioc')
            ->set('data.hero.eyebrow.en', 'Cassava kingdom')
            ->set('data.mission.title.fr', 'Titre en français seulement')
            ->call('save', 'hero')
            ->call('save', 'mission')
            ->assertHasNoErrors();

        $this->withCookie('locale', 'en')->get('/')
            ->assertSee('Cassava kingdom')
            // Pas de traduction anglaise saisie pour "mission" -> repli sur le français.
            ->assertSee('Titre en français seulement');

        // withCookie() reste actif pour la requête suivante dans le même test : on le repasse
        // explicitement à "fr" plutôt que de compter sur une absence de cookie.
        $this->withCookie('locale', 'fr')->get('/')
            ->assertSee('Royaume du manioc')
            ->assertDontSee('Cassava kingdom');
    }

    public function test_admin_can_upload_an_image_for_a_section(): void
    {
        Storage::fake('public');

        Livewire::test(ContentManager::class)
            ->set('imageFiles.hero.image', UploadedFile::fake()->image('bandeau.jpg'))
            ->call('save', 'hero')
            ->assertHasNoErrors();

        $src = SiteContent::where('key', 'hero')->value('data')['image']['src'];
        $this->assertStringStartsWith('storage/site-content/', $src);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $src));
    }

    public function test_admin_can_remove_an_image_from_a_section(): void
    {
        SiteContent::where('key', 'hero')->update(['data' => ['image' => ['src' => 'storage/site-content/old.jpg', 'alt' => '']]]);

        Livewire::test(ContentManager::class)
            ->call('removeImage', 'data.hero.image')
            ->call('save', 'hero')
            ->assertHasNoErrors();

        $this->assertNull(SiteContent::where('key', 'hero')->value('data')['image']['src'] ?? null);
    }

    public function test_admin_can_add_and_edit_a_testimonial(): void
    {
        $before = Testimonial::count();

        Livewire::test(ContentManager::class)
            ->call('addTestimonial')
            ->set('testimonials.'.$before.'.quote', 'Un retour très positif du terrain.')
            ->set('testimonials.'.$before.'.author_name', 'Test User')
            ->call('saveTestimonial', $before)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('testimonials', [
            'author_name' => 'Test User',
            'quote' => 'Un retour très positif du terrain.',
        ]);
    }

    public function test_admin_can_add_a_partner_with_a_logo(): void
    {
        Storage::fake('public');
        $before = Partner::count();

        Livewire::test(ContentManager::class)
            ->call('addPartner')
            ->set('partners.'.$before.'.name', 'Enabel')
            ->set('partnerLogos.'.$before, UploadedFile::fake()->image('enabel.jpg'))
            ->call('savePartner', $before)
            ->assertHasNoErrors();

        $partner = Partner::where('name', 'Enabel')->firstOrFail();
        $this->assertNotNull($partner->logo_path);
        Storage::disk('public')->assertExists($partner->logo_path);
    }

    public function test_admin_can_delete_a_partner(): void
    {
        $partner = Partner::create(['name' => 'À retirer', 'position' => 1]);

        Livewire::test(ContentManager::class)
            ->call('deletePartner', $partner->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
    }
}
