<?php

namespace Tests\Feature\Learner;

use App\Models\Formation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LearnerSpaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
        $this->learner = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
    }

    public function test_all_learner_pages_load(): void
    {
        $paid = Formation::published()->where('price', '>', 0)->first();
        $free = Formation::published()->where('price', 0)->first();

        $urls = [
            route('learner.dashboard'),
            route('learner.catalog'),
            route('learner.progress'),
            route('learner.marketplace'),
            route('learner.orders'),
            route('learner.community'),
            route('learner.course', $free),
            route('learner.checkout', $paid),
        ];

        foreach ($urls as $url) {
            $this->actingAs($this->learner)->get($url)->assertOk();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('learner.dashboard'))->assertRedirect(route('login'));
    }

    public function test_suspended_account_is_logged_out(): void
    {
        $suspended = User::factory()->suspended()->create(['role' => 'apprenant']);

        $this->actingAs($suspended)->get(route('learner.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_learner_cannot_open_a_paid_course_without_enrolment(): void
    {
        $paid = Formation::published()->where('price', '>', 0)
            ->whereDoesntHave('enrollments', fn ($q) => $q->where('user_id', $this->learner->id))
            ->firstOrFail();

        $this->actingAs($this->learner)->get(route('learner.course', $paid))->assertForbidden();
    }
}
