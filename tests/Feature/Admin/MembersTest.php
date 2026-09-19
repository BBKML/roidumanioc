<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Members;
use App\Mail\MemberPasswordResetMail;
use App\Mail\MemberWelcomeMail;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class MembersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Un']);
        $this->actingAs($this->admin);
    }

    /**
     * Audit architecture : tri par colonne ajouté sur ce tableau (aucun tableau du
     * back-office n'en avait). Clic 1 = ascendant, clic 2 sur la même colonne = descendant.
     */
    public function test_clicking_a_sortable_column_toggles_order(): void
    {
        User::factory()->create(['name' => 'Zoé', 'role' => 'apprenant']);
        User::factory()->create(['name' => 'Amadou', 'role' => 'apprenant']);

        Livewire::test(Members::class)
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'asc')
            ->assertViewHas('members', fn ($members) => $members->pluck('name')->first() === 'Admin Un') // ordre alphabétique : "Admin Un" < "Amadou" < "Zoé"
            ->call('sortBy', 'name')
            ->assertSet('direction', 'desc')
            ->assertViewHas('members', fn ($members) => $members->pluck('name')->first() === 'Zoé');
    }

    /** Sécurité : $sort vient de l'URL — une colonne non autorisée doit être ignorée, jamais injectée dans la requête. */
    public function test_sorting_by_an_unknown_column_is_ignored(): void
    {
        Livewire::test(Members::class)
            ->call('sortBy', 'password')
            ->assertSet('sort', 'created_at')
            ->assertSuccessful();
    }

    public function test_admin_creates_a_member_and_gets_a_one_time_password(): void
    {
        Livewire::test(Members::class)
            ->call('newMember')
            ->set('name', 'Nouvelle Apprenante')
            ->set('email', 'nouvelle@example.ci')
            ->set('role', 'apprenant')
            ->call('createMember')
            ->assertHasNoErrors()
            ->assertSet('tempPasswordFor', 'Nouvelle Apprenante')
            ->assertNotSet('tempPassword', null);

        $this->assertDatabaseHas('users', ['email' => 'nouvelle@example.ci', 'role' => 'apprenant', 'status' => 'actif']);
        $this->assertNull(User::where('email', 'nouvelle@example.ci')->value('password_changed_at'));
    }

    public function test_admin_creates_a_member_with_only_a_phone_number(): void
    {
        Livewire::test(Members::class)
            ->call('newMember')
            ->set('name', 'Sans E-mail')
            ->set('phone', '07 12 34 56 78')
            ->set('role', 'apprenant')
            ->call('createMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['name' => 'Sans E-mail', 'phone' => '0712345678', 'email' => null]);
    }

    public function test_creating_a_member_with_an_email_sends_a_welcome_email(): void
    {
        Mail::fake();

        Livewire::test(Members::class)
            ->call('newMember')
            ->set('name', 'Nouvelle Apprenante')
            ->set('email', 'nouvelle@example.ci')
            ->set('role', 'apprenant')
            ->call('createMember')
            ->assertHasNoErrors();

        Mail::assertSent(MemberWelcomeMail::class, fn ($mail) => $mail->hasTo('nouvelle@example.ci') && $mail->password !== '');
    }

    public function test_creating_a_member_without_an_email_sends_no_welcome_email(): void
    {
        Mail::fake();

        Livewire::test(Members::class)
            ->call('newMember')
            ->set('name', 'Sans E-mail')
            ->set('phone', '07 12 34 56 78')
            ->set('role', 'apprenant')
            ->call('createMember')
            ->assertHasNoErrors();

        Mail::assertNothingSent();
    }

    public function test_admin_cannot_create_a_member_without_email_or_phone(): void
    {
        Livewire::test(Members::class)
            ->call('newMember')
            ->set('name', 'Sans Identifiant')
            ->set('role', 'apprenant')
            ->call('createMember')
            ->assertHasErrors(['email', 'phone']);
    }

    public function test_cannot_act_on_own_account(): void
    {
        Livewire::test(Members::class)->call('changeRole', $this->admin->id)->assertForbidden();
        Livewire::test(Members::class)->call('toggleSuspend', $this->admin->id)->assertForbidden();
        Livewire::test(Members::class)->call('deleteMember', $this->admin->id)->assertForbidden();
        Livewire::test(Members::class)->call('resetPassword', $this->admin->id)->assertForbidden();
    }

    public function test_the_only_admin_is_protected_from_removal(): void
    {
        // $this->admin est le seul administrateur du système.
        $this->assertTrue($this->admin->fresh()->isLastActiveAdmin());

        Livewire::test(Members::class)->call('changeRole', $this->admin->id)->assertForbidden(); // soi-même

        // Une action déclenchée sur le dernier admin (garde métier, hors self) ne le retire pas.
        $learner = User::factory()->create(['role' => 'apprenant']);
        Livewire::actingAs($learner)->test(Members::class)->call('deleteMember', $this->admin->id);
        $this->assertModelExists($this->admin);

        Livewire::actingAs($learner)->test(Members::class)->call('changeRole', $this->admin->id);
        $this->assertTrue($this->admin->fresh()->isAdmin());

        Livewire::actingAs($learner)->test(Members::class)->call('toggleSuspend', $this->admin->id);
        $this->assertTrue($this->admin->fresh()->isActive());
    }

    public function test_demoting_one_of_two_admins_is_allowed(): void
    {
        $b = User::factory()->admin()->create();

        Livewire::test(Members::class)->call('changeRole', $b->id);
        $this->assertSame('apprenant', $b->fresh()->role->value);
    }

    public function test_deleting_a_member_with_payment_history_is_blocked(): void
    {
        $learner = User::factory()->create(['role' => 'apprenant']);
        $formation = Formation::create(['title' => 'X', 'category' => 'C', 'price' => 15000, 'status' => 'publiee']);
        Payment::create([
            'user_id' => $learner->id, 'payable_type' => Formation::class, 'payable_id' => $formation->id,
            'label' => 'X', 'amount' => 15000, 'method' => 'wave', 'declared_amount' => 15000,
        ]);

        Livewire::test(Members::class)->call('deleteMember', $learner->id);

        $this->assertModelExists($learner);
    }

    public function test_admin_resets_a_member_password_and_it_is_logged(): void
    {
        $learner = User::factory()->create(['role' => 'apprenant', 'password_changed_at' => now()]);
        $oldHash = $learner->password;

        Livewire::test(Members::class)
            ->call('resetPassword', $learner->id)
            ->assertNotSet('tempPassword', null);

        $learner->refresh();
        $this->assertNotSame($oldHash, $learner->password);
        $this->assertNull($learner->password_changed_at);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'password_reset_by_admin',
            'subject_id' => $learner->id,
            'causer_id' => $this->admin->id,
        ]);
    }

    public function test_resetting_a_members_password_emails_the_new_password(): void
    {
        Mail::fake();
        $learner = User::factory()->create(['role' => 'apprenant', 'email' => 'learner@example.ci']);

        Livewire::test(Members::class)->call('resetPassword', $learner->id);

        Mail::assertSent(MemberPasswordResetMail::class, fn ($mail) => $mail->hasTo('learner@example.ci'));
    }

    public function test_admin_suspends_and_reactivates_a_learner(): void
    {
        $learner = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);

        Livewire::test(Members::class)->call('toggleSuspend', $learner->id);
        $this->assertSame('suspendu', $learner->fresh()->status->value);

        Livewire::test(Members::class)->call('toggleSuspend', $learner->id);
        $this->assertSame('actif', $learner->fresh()->status->value);
    }

    /**
     * Audit UX (priorités 6 et 7) : le tableau se transforme en cartes sous ~700px
     * (classe CSS opt-in, cf. admin.css) et la suspension d'un compte — action à fort
     * impact — demande désormais une confirmation, comme la réactivation du paiement à
     * la livraison ou la suppression d'un compte.
     */
    public function test_the_members_table_is_mobile_ready_and_suspend_requires_confirmation(): void
    {
        User::factory()->create(['role' => 'apprenant', 'name' => 'Aïcha Coulibaly']);

        Livewire::test(Members::class)
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="E-mail"', false)
            ->assertSee('data-confirm="Suspendre le compte de Aïcha Coulibaly', false);
    }

    /**
     * Audit UX (Phase 2) : une recherche/filtre sans résultat explique la situation et
     * propose de repartir de zéro plutôt qu'un texte muet ("Aucun membre trouvé.").
     */
    public function test_a_search_with_no_results_offers_to_reset_it(): void
    {
        Livewire::test(Members::class)
            ->set('search', 'Personne Ne Porte Ce Nom')
            ->assertSee('Aucun membre ne correspond à cette recherche.')
            ->assertSee('Réinitialiser la recherche')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('filter', 'tous');
    }

    public function test_members_screen_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'apprenant']))
            ->get(route('admin.members'))->assertForbidden();
    }

    public function test_the_member_list_is_paginated(): void
    {
        User::factory()->count(30)->create(['role' => 'apprenant']);

        Livewire::test(Members::class)
            ->assertViewHas('members', fn ($members) => $members->perPage() === 25 && $members->hasPages())
            ->call('gotoPage', 2)
            ->assertViewHas('members', fn ($members) => $members->currentPage() === 2);
    }

    public function test_searching_resets_to_the_first_page(): void
    {
        User::factory()->count(30)->create(['role' => 'apprenant']);

        Livewire::test(Members::class)
            ->call('gotoPage', 2)
            ->set('search', 'Admin')
            ->assertViewHas('members', fn ($members) => $members->currentPage() === 1);
    }
}
