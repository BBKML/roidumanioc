<?php

namespace Tests\Feature\Admin;

use App\Models\Formation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', 'admin')->firstOrFail();
    }

    public function test_all_admin_pages_load_for_an_admin(): void
    {
        $formation = Formation::first();

        $routes = [
            route('admin.dashboard'),
            route('admin.payments'),
            route('admin.orders'),
            route('admin.messages'),
            route('admin.content'),
            route('admin.formations'),
            route('admin.lessons', $formation),
            route('admin.marketplace'),
            route('admin.shop'),
            route('admin.events'),
            route('admin.community'),
            route('admin.members'),
            route('admin.newsletter'),
            route('admin.activity'),
            route('admin.settings'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_learner_cannot_reach_the_back_office(): void
    {
        $learner = User::where('role', 'apprenant')->firstOrFail();

        $this->actingAs($learner)->get(route('admin.content'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
