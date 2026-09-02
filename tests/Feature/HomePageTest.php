<?php

namespace Tests\Feature;

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
            ->assertSee('Le manioc, matière royale.')
            ->assertSee("L'or des visionnaires");
    }

    public function test_home_lists_only_published_formations(): void
    {
        $this->get('/')
            ->assertSee('Réussir la culture du manioc')      // publiée
            ->assertDontSee('Gestion et commercialisation');  // brouillon
    }
}
