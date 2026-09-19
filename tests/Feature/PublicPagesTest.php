<?php

namespace Tests\Feature;

use App\Models\Formation;
use App\Models\Partner;
use App\Models\SiteContent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_formations_index_lists_only_published_formations(): void
    {
        $this->get(route('formations.index'))
            ->assertOk()
            ->assertSee('Réussir la culture du manioc')
            ->assertDontSee('Gestion et commercialisation');
    }

    public function test_formation_show_page_renders_a_published_formation(): void
    {
        $formation = Formation::where('title', 'Réussir la culture du manioc')->firstOrFail();

        $this->get(route('formations.show', $formation))
            ->assertOk()
            ->assertSee($formation->title)
            ->assertSee($formation->description);
    }

    public function test_formation_show_page_404s_for_a_draft_formation(): void
    {
        $formation = Formation::where('title', 'Gestion et commercialisation')->firstOrFail();

        $this->get(route('formations.show', $formation))->assertNotFound();
    }

    public function test_marketplace_index_shows_listings_and_shop_products(): void
    {
        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('Boutique officielle');
    }

    public function test_marketplace_shares_the_mode_selector_with_the_producers_catalog(): void
    {
        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee(route('producers.index'), false)
            ->assertSeeInOrder(['role="tab" aria-selected="false" class="">Négocier avec un producteur', 'role="tab" aria-selected="true" class="active">Commander directement'], false);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertSee(route('marketplace.index'), false)
            ->assertSeeInOrder(['role="tab" aria-selected="true" class="active">Négocier avec un producteur', 'role="tab" aria-selected="false" class="">Commander directement'], false);
    }

    public function test_marketplace_index_can_be_searched_and_filtered_by_type(): void
    {
        // Recherche : ne garde que l'annonce dont le titre correspond, le reste disparaît.
        $this->get(route('marketplace.index', ['q' => 'tonnes de manioc']))
            ->assertOk()
            ->assertSee('10 tonnes de manioc frais')
            ->assertDontSee('Boutures améliorées');

        // Filtre par type : seule l'annonce "Bouture" reste, et les produits boutique (qui
        // n'ont pas de type Récolte/Bouture/...) sortent des résultats.
        $this->get(route('marketplace.index', ['type' => 'Bouture']))
            ->assertOk()
            ->assertSee('Boutures améliorées')
            ->assertDontSee('10 tonnes de manioc frais')
            ->assertDontSee('Boutique officielle');
    }

    public function test_formations_index_shows_the_real_awards_strip(): void
    {
        $this->get(route('formations.index'))
            ->assertOk()
            ->assertSee('awards-strip', false)
            ->assertSee("Éléphant d'Or — Super Prix National");
    }

    public function test_communaute_page_shows_partner_logos_when_published(): void
    {
        Partner::create(['name' => 'Enabel', 'logo_path' => 'partners/enabel.jpg', 'position' => 1]);

        $this->get(route('communaute.show'))
            ->assertOk()
            ->assertSee('partners-strip', false)
            ->assertSee('alt="Enabel"', false);
    }

    public function test_communaute_page_hides_the_partners_strip_without_any_logo(): void
    {
        $this->get(route('communaute.show'))
            ->assertOk()
            ->assertDontSee('partners-strip', false);
    }

    public function test_placali_page_renders_cms_content(): void
    {
        $this->get(route('placali.show'))
            ->assertOk()
            ->assertSee('Placali du Roi');
    }

    /**
     * Audit UX (Phase 3) : aucun checkout Placali n'existe — le bouton ne doit plus
     * promettre une commande en ligne (texte "Commander...") ni mener à /login, mais
     * vers la page dédiée /placali.
     */
    public function test_placali_button_no_longer_promises_an_order(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Découvrir le Placali du Roi')
            ->assertDontSee('Commander le Placali du Roi');
    }

    /**
     * Audit UX (Phase 4) : la section Placali de l'accueil avait deux boutons identiques
     * (même texte, même destination) — un seul suffit désormais. La page dédiée /placali
     * n'a plus de bouton auto-référent vers elle-même (rien d'utile à proposer à la place
     * sans créer de nouvelle fonctionnalité).
     */
    public function test_the_home_placali_section_has_no_duplicate_button(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        // D'autres liens légitimes vers /placali existent ailleurs sur la page (le pilier
        // "Placali du Roi", le pied de page) — seule la section Placali elle-même
        // (id="placali") est concernée par le doublon signalé.
        $start = strpos($html, 'id="placali"');
        $end = strpos($html, '</section>', $start);
        $section = substr($html, $start, $end - $start);

        $this->assertSame(1, substr_count($section, 'href="'.route('placali.show').'"'));
    }

    public function test_the_placali_page_has_no_self_referencing_button(): void
    {
        $html = $this->get(route('placali.show'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Découvrir le Placali du Roi', $html);
        $this->assertStringNotContainsString('Commander le Placali du Roi', $html);
    }

    public function test_communaute_page_lists_testimonials(): void
    {
        $this->get(route('communaute.show'))
            ->assertOk()
            ->assertSee('Ahou S.');
    }

    public function test_fondateur_page_shows_founder_and_awards(): void
    {
        $this->get(route('fondateur.show'))
            ->assertOk()
            ->assertSee('Seggo Kobenan Michel')
            ->assertSee("Éléphant d'Or");
    }

    public function test_home_navigation_links_to_the_dedicated_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('formations.index'), false)
            ->assertSee(route('marketplace.index'), false)
            ->assertSee(route('placali.show'), false)
            ->assertSee(route('communaute.show'), false)
            ->assertSee(route('fondateur.show'), false);
    }

    /* ------------------------------------------------------------------ *
     |  Pied de page (audit UX, Phase 2) : doublons et lien mort nettoyés
     * ------------------------------------------------------------------ */

    public function test_the_footer_has_a_single_member_area_link(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, __('site.footer.member_area')));
    }

    public function test_the_footer_city_is_a_real_map_link_when_coordinates_are_configured(): void
    {
        $pied = SiteContent::where('key', 'pied')->firstOrFail();
        $pied->update(['data' => array_merge($pied->data, [
            'map_lat' => '6.8206', 'map_lng' => '-5.2767',
        ])]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://www.google.com/maps?q=6.8206,-5.2767', false);
    }

    public function test_the_footer_city_is_plain_text_without_coordinates(): void
    {
        $pied = SiteContent::where('key', 'pied')->firstOrFail();
        $data = $pied->data;
        unset($data['map_lat'], $data['map_lng']);
        $pied->update(['data' => $data]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="#"', $html);
    }

    /* ------------------------------------------------------------------ *
     |  Mentions légales / confidentialité (§44)
     * ------------------------------------------------------------------ */

    public function test_legal_pages_show_a_placeholder_when_the_cms_content_is_empty(): void
    {
        $this->get(route('legal.notice'))
            ->assertOk()
            ->assertSee('Mentions légales')
            ->assertSee('sera complétée prochainement');

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Politique de confidentialité')
            ->assertSee('sera complétée prochainement');

        // Charte d'utilisation (Phase 12, §44 — audit V1) : règles de publication/
        // comportement, litiges et paiement, regroupées dans ce même document.
        $this->get(route('legal.charter'))
            ->assertOk()
            ->assertSee("Charte d'utilisation")
            ->assertSee('sera complétée prochainement');
    }

    public function test_legal_pages_render_the_cms_content_once_filled_in(): void
    {
        SiteContent::updateOrCreate(['key' => 'legal'], [
            'label' => 'Mentions légales & confidentialité',
            'data' => [
                'mentions_legales' => '<p>Texte officiel des mentions légales.</p>',
                'politique_confidentialite' => '<p>Texte officiel de la politique de confidentialité.</p>',
                'charte_utilisation' => '<p>Texte officiel de la charte d\'utilisation.</p>',
            ],
        ]);

        $this->get(route('legal.notice'))->assertOk()->assertSee('Texte officiel des mentions légales.', false);
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Texte officiel de la politique de confidentialité.', false);
        $this->get(route('legal.charter'))->assertOk()->assertSee("Texte officiel de la charte d'utilisation.", false);
    }

    public function test_footer_links_to_the_legal_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('legal.notice'), false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.charter'), false);
    }

    public function test_sitemap_includes_the_new_public_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('formations.index'), false)
            ->assertSee(route('marketplace.index'), false)
            ->assertSee(route('placali.show'), false);
    }
}
