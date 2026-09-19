<?php

namespace Tests\Feature\Learner;

use App\Livewire\Learner\Catalog;
use App\Livewire\Learner\Checkout;
use App\Livewire\Learner\CourseViewer;
use App\Models\Formation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EnrollmentAndProgressTest extends TestCase
{
    use RefreshDatabase;

    protected User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
        $this->learner = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $this->actingAs($this->learner);
    }

    public function test_free_formation_enrols_instantly_and_grants_access(): void
    {
        $free = Formation::published()->where('price', 0)->firstOrFail();

        Livewire::test(Catalog::class)
            ->call('enrollFree', $free->id)
            ->assertRedirect(route('learner.course', $free));

        $this->assertTrue($this->learner->fresh()->isEnrolledIn($free));
        $this->actingAs($this->learner)->get(route('learner.course', $free))->assertOk();
    }

    public function test_paid_formation_goes_through_checkout_and_creates_a_pending_payment(): void
    {
        Storage::fake('local');
        $paid = Formation::published()->where('price', '>', 0)->firstOrFail();

        Livewire::test(Catalog::class)
            ->call('buy', $paid->id)
            ->assertRedirect(route('learner.checkout', $paid));

        Livewire::test(Checkout::class, ['formation' => $paid])
            ->set('method', 'wave')
            ->set('declaredAmount', $paid->price)
            ->set('transactionId', 'TX-UNIQUE-001')
            ->set('proof', UploadedFile::fake()->image('recu.jpg', 800, 1000))
            ->call('declarePayment')
            ->assertRedirect(route('learner.dashboard'));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->learner->id,
            'formation_id' => $paid->id,
            'status' => 'paiement',
        ]);
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->learner->id,
            'amount' => $paid->price,
            'status' => 'a_verifier',
        ]);
        // Le contrôle automatique a tourné : capture présente, montant conforme, risque faible.
        $payment = $this->learner->payments()->latest('id')->first();
        $this->assertSame('ok', $payment->check_result['proof']);
        $this->assertSame('ok', $payment->check_result['amount']);
        $this->assertSame('low', $payment->check_result['risk']);
        $this->assertSame($paid->price, $payment->declared_amount);
        $this->assertNotNull($payment->proof_hash);
        Storage::disk('local')->assertExists($payment->proof_path);
    }

    public function test_marking_lessons_complete_updates_progress(): void
    {
        $free = Formation::published()->where('price', 0)->has('lessons', '>=', 2)->firstOrFail();

        $component = Livewire::test(CourseViewer::class, ['formation' => $free]);

        // Première leçon sélectionnée par défaut -> on la termine.
        $component->call('toggleComplete');
        $this->assertSame(1, $this->learner->lessonProgress()->count());

        // On avance et on termine la suivante.
        $component->call('go', 'next')->call('toggleComplete');
        $this->assertSame(2, $this->learner->lessonProgress()->count());

        $pr = $free->progressFor($this->learner->fresh());
        $this->assertSame(2, $pr['done']);
        $this->assertGreaterThan(0, $pr['pct']);

        // Décocher fonctionne aussi.
        $component->call('toggleComplete');
        $this->assertSame(1, $this->learner->lessonProgress()->count());
    }
}
