<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Vérifie que les listes potentiellement vides des pages publiques affichent un état
 * vide explicite en français plutôt qu'un espace silencieux — volontairement séparé de
 * `PublicPagesTest` (qui seed toujours des données de démo) : ici la base ne contient
 * aucune formation/offre/témoignage, sans avoir à en supprimer après coup.
 */
class PublicEmptyStatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_formations_index_shows_an_explicit_empty_state(): void
    {
        $this->get(route('formations.index'))
            ->assertOk()
            ->assertSee('Aucune formation publiée pour le moment.');
    }

    public function test_formations_index_hides_the_awards_strip_without_any_award(): void
    {
        $this->get(route('formations.index'))
            ->assertOk()
            ->assertDontSee('awards-strip', false);
    }

    public function test_marketplace_index_shows_an_explicit_empty_state(): void
    {
        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('Aucune offre disponible pour le moment.');
    }

    public function test_communaute_page_shows_an_explicit_empty_state(): void
    {
        $this->get(route('communaute.show'))
            ->assertOk()
            ->assertSee('Aucun témoignage pour le moment.');
    }

    public function test_fondateur_page_shows_an_explicit_empty_state_for_awards(): void
    {
        $this->get(route('fondateur.show'))
            ->assertOk()
            ->assertSee('Aucune distinction pour le moment.');
    }

    public function test_the_404_page_uses_the_vitrine_design(): void
    {
        $this->get('/une-page-qui-n-existe-pas')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee("Retour à l'accueil", false);
    }
}
