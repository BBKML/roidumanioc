<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RegistrationFormBuilder;
use App\Models\RegistrationForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationFormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_creates_a_registration_form_with_an_auto_generated_slug(): void
    {
        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Directeur Commercial & Marketing')
            ->set('slug', 'directeur-commercial-marketing')
            ->set('status', 'publiee')
            ->call('addItem', 'objectives')
            ->set('items.objectives.0.text', 'Acquérir des compétences')
            ->call('addItem', 'objectives')
            ->set('items.objectives.1.text', 'Maîtriser les outils')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.registration-forms.edit', 'directeur-commercial-marketing'));

        $form = RegistrationForm::firstOrFail();
        $this->assertSame('directeur-commercial-marketing', $form->slug);
        $this->assertSame('publiee', $form->status->value);
        $this->assertSame(['Acquérir des compétences', 'Maîtriser les outils'], $form->lines('objectives'));
    }

    /**
     * Audit UX (Phase 2) : ces 5 champs étaient validés côté serveur mais leur erreur
     * n'était jamais affichée dans la vue — un échec de validation restait invisible.
     */
    public function test_validation_errors_are_displayed_for_previously_silent_fields(): void
    {
        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Formation test')
            ->set('slug', 'formation-test')
            ->set('subtitle', str_repeat('a', 201))
            ->set('intro', str_repeat('a', 2001))
            ->set('whatsapp_number', str_repeat('1', 41))
            ->set('price_amount', str_repeat('a', 121))
            ->set('status', 'archive')
            ->call('save')
            ->assertHasErrors(['subtitle', 'intro', 'whatsapp_number', 'price_amount', 'status'])
            ->assertSee('Le champ sous-titre ne doit pas dépasser 200 caractères.')
            ->assertSee('Le champ intro ne doit pas dépasser 2000 caractères.')
            ->assertSee('Le champ numéro WhatsApp ne doit pas dépasser 40 caractères.')
            ->assertSee('Le champ tarif affiché ne doit pas dépasser 120 caractères.')
            ->assertSee('La valeur sélectionnée pour statut est invalide.');
    }

    public function test_slug_must_be_unique(): void
    {
        RegistrationForm::create(['title' => 'Existant', 'slug' => 'existant', 'status' => 'brouillon']);

        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Autre')
            ->set('slug', 'existant')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    public function test_admin_uploads_a_cover_image(): void
    {
        Storage::fake('public');

        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Avec affiche')
            ->set('slug', 'avec-affiche')
            ->set('coverImage', UploadedFile::fake()->image('affiche.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $form = RegistrationForm::where('slug', 'avec-affiche')->firstOrFail();
        $this->assertNotNull($form->cover_image_path);
        Storage::disk('public')->assertExists($form->cover_image_path);
        $this->assertSame(route('inscription.image', $form->slug), $form->coverImageUrl());
        $this->get($form->coverImageUrl())->assertOk();
    }

    public function test_admin_removes_the_cover_image(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('affiche.jpg')->store('registration-forms', 'public');
        $form = RegistrationForm::create([
            'title' => 'Avec affiche', 'slug' => 'avec-affiche', 'status' => 'brouillon',
            'cover_image_path' => $path,
        ]);

        Livewire::test(RegistrationFormBuilder::class, ['form' => $form])
            ->call('removeCoverImage')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($form->fresh()->cover_image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_item_editor_adds_reorders_and_removes_points_without_markdown(): void
    {
        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Avec éditeur de points')
            ->set('slug', 'avec-editeur-de-points')
            ->call('addItem', 'objectives')
            ->set('items.objectives.0.label', 'Maîtriser')
            ->set('items.objectives.0.text', 'les techniques modernes')
            ->call('addItem', 'objectives')
            ->set('items.objectives.1.text', 'Transformer le manioc')
            ->call('addItem', 'objectives', 'heading')
            ->set('items.objectives.2.text', 'Atouts')
            ->call('moveItem', 'objectives', 2, -1)
            ->call('save')
            ->assertHasNoErrors();

        $form = RegistrationForm::where('slug', 'avec-editeur-de-points')->firstOrFail();
        $this->assertSame(
            "**Maîtriser** les techniques modernes\n## Atouts\nTransformer le manioc",
            $form->objectives
        );
    }

    public function test_item_editor_remove_deletes_the_point(): void
    {
        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Avec suppression')
            ->set('slug', 'avec-suppression')
            ->call('addItem', 'program')
            ->set('items.program.0.text', 'Premier point')
            ->call('addItem', 'program')
            ->set('items.program.1.text', 'Deuxième point')
            ->call('removeItem', 'program', 0)
            ->call('save')
            ->assertHasNoErrors();

        $form = RegistrationForm::where('slug', 'avec-suppression')->firstOrFail();
        $this->assertSame('Deuxième point', $form->program);
    }

    public function test_editing_an_existing_form_reloads_its_points_into_the_item_editor(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Existant', 'slug' => 'existant-items', 'status' => 'brouillon',
            'objectives' => "**Rentrée :** 27 septembre\n## Atouts\nFlexibilité totale",
        ]);

        Livewire::test(RegistrationFormBuilder::class, ['form' => $form])
            ->assertSet('items.objectives.0.label', 'Rentrée :')
            ->assertSet('items.objectives.0.text', '27 septembre')
            ->assertSet('items.objectives.1.type', 'heading')
            ->assertSet('items.objectives.1.text', 'Atouts')
            ->assertSet('items.objectives.2.text', 'Flexibilité totale');
    }

    public function test_saving_an_existing_form_stays_on_the_same_page(): void
    {
        $form = RegistrationForm::create(['title' => 'Existant', 'slug' => 'existant-save', 'status' => 'brouillon']);

        Livewire::test(RegistrationFormBuilder::class, ['form' => $form])
            ->set('subtitle', 'Nouveau sous-titre')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('justSaved', true)
            ->assertNoRedirect();

        $this->assertSame('Nouveau sous-titre', $form->fresh()->subtitle);
    }

    public function test_live_preview_renders_the_public_page_html(): void
    {
        $component = Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Aperçu Live')
            ->call('addItem', 'objectives')
            ->set('items.objectives.0.text', 'Un objectif concret');

        $html = $component->instance()->previewHtml();

        $this->assertStringContainsString('Aperçu Live', $html);
        $this->assertStringContainsString('Un objectif concret', $html);
        $this->assertStringContainsString('Aperçu — ceci n', $html);
    }

    public function test_admin_sets_an_early_bird_deadline_and_a_deposit_amount(): void
    {
        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Avec offre premiers inscrits')
            ->set('slug', 'avec-offre-premiers-inscrits')
            ->set('early_bird_deadline', '2026-10-01T23:59')
            ->set('deposit_amount', '50 000 FCFA')
            ->set('deposit_note', 'Solde à régler selon les modalités convenues.')
            ->call('save')
            ->assertHasNoErrors();

        $form = RegistrationForm::where('slug', 'avec-offre-premiers-inscrits')->firstOrFail();
        $this->assertSame('2026-10-01 23:59:00', $form->early_bird_deadline->format('Y-m-d H:i:s'));
        $this->assertSame('50 000 FCFA', $form->deposit_amount);
        $this->assertSame('Solde à régler selon les modalités convenues.', $form->deposit_note);
    }

    public function test_blank_early_bird_deadline_is_stored_as_null(): void
    {
        Livewire::test(RegistrationFormBuilder::class)
            ->set('title', 'Sans échéance')
            ->set('slug', 'sans-echeance')
            ->set('early_bird_deadline', '')
            ->call('save')
            ->assertHasNoErrors();

        $form = RegistrationForm::where('slug', 'sans-echeance')->firstOrFail();
        $this->assertNull($form->early_bird_deadline);
    }

    public function test_editing_an_existing_form_reloads_the_deadline_and_deposit_fields(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Existant', 'slug' => 'existant-offre', 'status' => 'brouillon',
            'early_bird_deadline' => '2026-11-15 18:30:00',
            'deposit_amount' => '50 000 FCFA',
        ]);

        Livewire::test(RegistrationFormBuilder::class, ['form' => $form])
            ->assertSet('early_bird_deadline', '2026-11-15T18:30')
            ->assertSet('deposit_amount', '50 000 FCFA');
    }

    public function test_guest_and_learner_cannot_access(): void
    {
        $this->post('/logout');
        $this->get(route('admin.registration-forms.create'))->assertRedirect(route('login'));

        $learner = User::factory()->create(['role' => 'apprenant']);
        $this->actingAs($learner)->get(route('admin.registration-forms.create'))->assertForbidden();
    }
}
