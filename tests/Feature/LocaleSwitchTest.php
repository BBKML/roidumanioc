<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_page_defaults_to_french_interface(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Aller au contenu')
            ->assertSee('Ils nous distinguent');
    }

    public function test_switching_to_english_translates_the_interface_but_not_cms_content(): void
    {
        $this->withCookie('locale', 'en')
            ->get('/')
            ->assertOk()
            ->assertSee('Skip to content')
            ->assertSee('Trusted by')
            ->assertSee("L'or des visionnaires"); // contenu CMS, reste en français
    }

    public function test_locale_switch_route_sets_the_cookie_and_redirects_back(): void
    {
        $response = $this->from('/')->get('/langue/en');

        $response->assertRedirect('/');
        $response->assertCookie('locale', 'en');
    }

    public function test_locale_switch_rejects_unsupported_locales(): void
    {
        $this->get('/langue/de')->assertNotFound();
    }

    public function test_login_page_translates_to_english(): void
    {
        $this->withCookie('locale', 'en')
            ->get('/login')
            ->assertOk()
            ->assertSee('Log in')
            ->assertDontSee('Se connecter');
    }

    public function test_contact_page_translates_to_english(): void
    {
        $this->withCookie('locale', 'en')
            ->get('/contact')
            ->assertOk()
            ->assertSee('Write to us')
            ->assertDontSee('Écrivez-nous');
    }
}
