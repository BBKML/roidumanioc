<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RegistrationLeads;
use App\Models\RegistrationForm;
use App\Models\RegistrationLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationLeadsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    private function makeLead(RegistrationForm $form, array $overrides = []): RegistrationLead
    {
        return RegistrationLead::create(array_merge([
            'registration_form_id' => $form->id,
            'gender' => 'Homme',
            'last_name' => 'Kouadio',
            'first_name' => 'Bertin',
            'email' => 'bertin@example.ci',
            'phone_1' => '0700000000',
        ], $overrides));
    }

    public function test_lists_and_filters_leads_by_status(): void
    {
        $form = RegistrationForm::create(['title' => 'Test', 'slug' => 'test', 'status' => 'publiee']);
        $this->makeLead($form, ['last_name' => 'Kouadio', 'status' => 'nouveau']);
        $this->makeLead($form, ['last_name' => 'Traore', 'status' => 'contacte']);

        Livewire::test(RegistrationLeads::class)
            ->assertSee('Kouadio')
            ->call('setFilter', 'contacte')
            ->assertSee('Traore')
            ->assertDontSee('Kouadio');
    }

    public function test_search_filters_leads(): void
    {
        $form = RegistrationForm::create(['title' => 'Test', 'slug' => 'test', 'status' => 'publiee']);
        $this->makeLead($form, ['last_name' => 'Kouadio']);
        $this->makeLead($form, ['last_name' => 'Traore']);

        Livewire::test(RegistrationLeads::class)
            ->call('setFilter', 'tous')
            ->set('search', 'Traore')
            ->assertSee('Traore')
            ->assertDontSee('Kouadio');
    }

    public function test_mark_contacted_enrolled_and_reopen(): void
    {
        $form = RegistrationForm::create(['title' => 'Test', 'slug' => 'test', 'status' => 'publiee']);
        $lead = $this->makeLead($form);

        $component = Livewire::test(RegistrationLeads::class);

        $component->call('markContacted', $lead->id);
        $this->assertSame('contacte', $lead->fresh()->status->value);

        $component->call('markEnrolled', $lead->id);
        $this->assertSame('inscrit', $lead->fresh()->status->value);

        $component->call('markAbandoned', $lead->id);
        $this->assertSame('abandonne', $lead->fresh()->status->value);

        $component->call('reopen', $lead->id);
        $this->assertSame('nouveau', $lead->fresh()->status->value);
    }

    public function test_delete_removes_the_lead(): void
    {
        $form = RegistrationForm::create(['title' => 'Test', 'slug' => 'test', 'status' => 'publiee']);
        $lead = $this->makeLead($form);

        Livewire::test(RegistrationLeads::class)->call('setFilter', 'tous')->call('delete', $lead->id);

        $this->assertNull(RegistrationLead::find($lead->id));
    }

    private function csvFrom(array $rows, string $delimiter = ','): UploadedFile
    {
        $content = implode("\n", array_map(fn ($row) => implode($delimiter, $row), $rows));

        return UploadedFile::fake()->createWithContent('prospects.csv', $content);
    }

    public function test_import_creates_leads_from_a_csv_file(): void
    {
        $form = RegistrationForm::create(['title' => 'Campagne', 'slug' => 'campagne', 'status' => 'publiee']);

        $file = $this->csvFrom([
            ['genre', 'nom', 'prenoms', 'email', 'telephone', 'whatsapp', 'profession', 'tranche_age', 'ville_pays', 'entreprise', 'moyen_paiement'],
            ['Femme', 'Kouassi', 'Awa', 'awa@example.ci', '0700000000', '0700000000', 'Productrice', '26-35 ans', 'Abidjan', '', 'Mobile Money'],
            ['Homme', 'Traore', 'Ibrahim', '', '0711111111', '', '', '', '', '', ''],
        ]);

        Livewire::test(RegistrationLeads::class)
            ->call('openImport')
            ->set('importFormId', $form->id)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importResult.created', 2)
            ->assertSet('importResult.duplicates', 0);

        $this->assertSame(2, RegistrationLead::where('registration_form_id', $form->id)->count());
        $this->assertDatabaseHas('registration_leads', [
            'registration_form_id' => $form->id,
            'first_name' => 'Awa',
            'last_name' => 'Kouassi',
            'email' => 'awa@example.ci',
            'gender' => 'Femme',
            'profession' => 'Productrice',
            'payment_method' => 'Mobile Money',
            'how_heard' => 'Import admin',
        ]);
        $this->assertDatabaseHas('registration_leads', [
            'registration_form_id' => $form->id,
            'first_name' => 'Ibrahim',
            'email' => null,
            'gender' => 'Homme',
        ]);
    }

    public function test_import_accepts_a_semicolon_delimited_file(): void
    {
        // Format réellement produit par le modèle téléchargeable (RegistrationLeadTemplateController)
        // et par l'export — point-virgule, pas virgule (séparateur de liste attendu par Excel en
        // locale française). L'import doit reconnaître les deux sans configuration.
        $form = RegistrationForm::create(['title' => 'Campagne', 'slug' => 'campagne', 'status' => 'publiee']);

        $file = $this->csvFrom([
            ['genre', 'nom', 'prenoms', 'email', 'telephone'],
            ['Femme', 'Kouassi', 'Awa', 'awa@example.ci', '0700000000'],
        ], ';');

        Livewire::test(RegistrationLeads::class)
            ->call('openImport')
            ->set('importFormId', $form->id)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importResult.created', 1);

        $this->assertDatabaseHas('registration_leads', [
            'registration_form_id' => $form->id,
            'first_name' => 'Awa',
            'email' => 'awa@example.ci',
        ]);
    }

    public function test_import_skips_prospects_already_registered_in_the_same_campaign(): void
    {
        $form = RegistrationForm::create(['title' => 'Campagne', 'slug' => 'campagne', 'status' => 'publiee']);
        $this->makeLead($form, ['email' => 'existing@example.ci', 'phone_1' => '0700000000']);

        $file = $this->csvFrom([
            ['genre', 'nom', 'prenoms', 'email', 'telephone', 'whatsapp', 'profession', 'tranche_age', 'ville_pays', 'entreprise', 'moyen_paiement'],
            // Doublon par e-mail.
            ['Homme', 'Kone', 'Yves', 'existing@example.ci', '0722222222', '', '', '', '', '', ''],
            // Doublon par téléphone, écrit avec indicatif — doit quand même être détecté.
            ['Femme', 'Bamba', 'Aya', 'autre@example.ci', '+225 07 00 00 00 00', '', '', '', '', '', ''],
            // Prospect réellement nouveau.
            ['Homme', 'Diallo', 'Seydou', 'nouveau@example.ci', '0733333333', '', '', '', '', '', ''],
        ]);

        Livewire::test(RegistrationLeads::class)
            ->call('openImport')
            ->set('importFormId', $form->id)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importResult.created', 1)
            ->assertSet('importResult.duplicates', 2);

        $this->assertSame(2, RegistrationLead::where('registration_form_id', $form->id)->count());
    }

    public function test_the_same_person_can_be_imported_into_a_different_campaign(): void
    {
        $formA = RegistrationForm::create(['title' => 'Campagne A', 'slug' => 'campagne-a', 'status' => 'publiee']);
        $formB = RegistrationForm::create(['title' => 'Campagne B', 'slug' => 'campagne-b', 'status' => 'publiee']);
        $this->makeLead($formA, ['email' => 'existing@example.ci', 'phone_1' => '0700000000']);

        $file = $this->csvFrom([
            ['nom', 'prenoms', 'email', 'telephone'],
            ['Kone', 'Yves', 'existing@example.ci', '0700000000'],
        ]);

        Livewire::test(RegistrationLeads::class)
            ->call('openImport')
            ->set('importFormId', $formB->id)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importResult.created', 1)
            ->assertSet('importResult.duplicates', 0);

        $this->assertSame(1, RegistrationLead::where('registration_form_id', $formB->id)->count());
    }

    public function test_import_reports_rows_missing_required_fields_without_failing_the_whole_file(): void
    {
        $form = RegistrationForm::create(['title' => 'Campagne', 'slug' => 'campagne', 'status' => 'publiee']);

        $file = $this->csvFrom([
            ['nom', 'prenoms', 'telephone'],
            ['', 'Yves', '0722222222'], // nom manquant
            ['Diallo', 'Seydou', '0733333333'], // valide
        ]);

        $component = Livewire::test(RegistrationLeads::class)
            ->call('openImport')
            ->set('importFormId', $form->id)
            ->set('importFile', $file)
            ->call('import')
            ->assertSet('importResult.created', 1);

        $this->assertCount(1, $component->get('importResult')['errors']);
        $this->assertSame(1, RegistrationLead::count());
    }

    public function test_import_requires_a_campaign_and_a_csv_file(): void
    {
        Livewire::test(RegistrationLeads::class)
            ->call('openImport')
            ->call('import')
            ->assertHasErrors(['importFormId', 'importFile']);

        $this->assertSame(0, RegistrationLead::count());
    }

    public function test_import_template_can_be_downloaded(): void
    {
        $response = $this->get(route('admin.registration-leads.template'));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_streams_a_csv_of_the_leads(): void
    {
        $form = RegistrationForm::create(['title' => 'Campagne', 'slug' => 'campagne', 'status' => 'publiee']);
        $this->makeLead($form, [
            'last_name' => 'Kouadio',
            'first_name' => 'Bertin',
            'email' => 'bertin@example.ci',
            'gender' => 'Homme',
            'phone_1' => '0700000000',
            'whatsapp' => '0711111111',
            'profession' => 'Producteur',
            'age_range' => '26 à 35 ans',
            'city_country' => 'Abidjan',
        ]);

        $response = $this->get(route('admin.registration-leads.export'));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $lines = array_values(array_filter(explode("\n", trim(ltrim($response->streamedContent(), "\xEF\xBB\xBF")))));

        $this->assertSame(
            ['nom', 'prenom', 'genre', 'telephone', 'telephone_whatsapp', 'email', 'profession', 'tranche_age', 'ville'],
            str_getcsv($lines[0], ';')
        );
        $this->assertSame(
            ['Kouadio', 'Bertin', 'Homme', '0700000000', '0711111111', 'bertin@example.ci', 'Producteur', '26 à 35 ans', 'Abidjan'],
            str_getcsv($lines[1], ';')
        );
    }

    public function test_export_respects_the_campaign_status_and_search_filters(): void
    {
        $formA = RegistrationForm::create(['title' => 'Campagne A', 'slug' => 'campagne-a', 'status' => 'publiee']);
        $formB = RegistrationForm::create(['title' => 'Campagne B', 'slug' => 'campagne-b', 'status' => 'publiee']);
        $this->makeLead($formA, ['last_name' => 'Kouadio', 'status' => 'nouveau']);
        $this->makeLead($formA, ['last_name' => 'Traore', 'status' => 'contacte']);
        $this->makeLead($formB, ['last_name' => 'Bamba', 'status' => 'nouveau']);

        $content = $this->get(route('admin.registration-leads.export', ['form' => $formA->id, 'status' => 'nouveau']))
            ->streamedContent();

        $this->assertStringContainsString('Kouadio', $content);
        $this->assertStringNotContainsString('Traore', $content);
        $this->assertStringNotContainsString('Bamba', $content);
    }

    public function test_guest_and_learner_cannot_access(): void
    {
        $this->post('/logout');
        $this->get(route('admin.registration-leads'))->assertRedirect(route('login'));

        $learner = User::factory()->create(['role' => 'apprenant']);
        $this->actingAs($learner)->get(route('admin.registration-leads'))->assertForbidden();
    }
}
