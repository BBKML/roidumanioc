<?php

namespace Tests\Feature;

use App\Mail\RegistrationLeadMail;
use App\Models\RegistrationForm;
use App\Models\RegistrationLead;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'gender' => 'Homme',
            'last_name' => 'Kouadio',
            'first_name' => 'Bertin',
            'email' => 'bertin@example.ci',
            'phone_1' => '0700000000',
            'whatsapp' => '0700000000',
            'experience_level' => '0 à 5 ans',
            'profession' => 'Producteur',
            'age_range' => '26 à 35 ans',
            'company' => 'Manioc SARL',
            'city_country' => 'Abidjan, Côte d\'Ivoire',
            'payment_method' => 'Mobile Money (Orange, MTN, Moov)',
        ], $overrides);
    }

    public function test_published_form_renders(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Directeur Commercial & Marketing',
            'status' => 'publiee',
            'objectives' => "Acquérir des compétences\nMaîtriser les outils",
        ]);

        $this->get(route('inscription.show', $form))
            ->assertOk()
            ->assertSee('Directeur Commercial & Marketing')
            ->assertSee('Acquérir des compétences');
    }

    public function test_bold_markdown_and_subheadings_render_as_html(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Avec mise en forme',
            'status' => 'publiee',
            'schedule_info' => "**Rentrée :** 27 septembre 2026\n## Atouts\nFlexibilité totale",
        ]);

        $response = $this->get(route('inscription.show', $form));

        $response->assertOk();
        $response->assertSee('<strong>Rentrée :</strong> 27 septembre 2026', false);
        $response->assertSee('<p class="gform-subheading">Atouts</p>', false);
        $response->assertDontSee('**Rentrée', false);
    }

    public function test_html_in_content_fields_is_escaped_not_injected(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Avec html',
            'status' => 'publiee',
            'objectives' => '<script>alert(1)</script>',
        ]);

        $this->get(route('inscription.show', $form))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_cover_image_renders_in_its_own_dedicated_section(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Avec affiche',
            'status' => 'publiee',
            'cover_image_path' => 'registration-forms/fake.jpg',
        ]);

        $response = $this->get(route('inscription.show', $form));

        $response->assertOk();
        $response->assertSee('class="gform-card gform-affiche"', false);
        $response->assertSee(route('inscription.image', $form), false);
    }

    public function test_no_affiche_section_when_no_cover_image(): void
    {
        $form = RegistrationForm::create(['title' => 'Sans affiche', 'status' => 'publiee']);

        $this->get(route('inscription.show', $form))
            ->assertOk()
            ->assertDontSee('class="gform-card gform-affiche"', false);
    }

    public function test_countdown_banner_renders_when_a_deadline_is_set(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Avec échéance',
            'status' => 'publiee',
            'early_bird_deadline' => now()->addDays(6)->addHours(14)->addMinutes(32),
        ]);

        $response = $this->get(route('inscription.show', $form));

        $response->assertOk();
        $response->assertSee('data-deadline="'.$form->fresh()->early_bird_deadline->toIso8601String().'"', false);
        $response->assertSee('Offre premiers inscrits');
        $response->assertSee('data-ring="days"', false);
        $response->assertSee('data-ring="hours"', false);
        $response->assertSee('data-ring="minutes"', false);
        $response->assertSee('data-ring="seconds"', false);
        $response->assertSee('data-countdown-ended', false);
    }

    public function test_no_countdown_banner_when_no_deadline_is_set(): void
    {
        $form = RegistrationForm::create(['title' => 'Sans échéance', 'status' => 'publiee']);

        $this->get(route('inscription.show', $form))
            ->assertOk()
            ->assertDontSee('data-deadline', false)
            ->assertDontSee('Offre premiers inscrits');
    }

    public function test_deposit_option_renders_alongside_full_payment(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Avec réservation',
            'status' => 'publiee',
            'price_amount' => '200 000 FCFA',
            'deposit_amount' => '50 000 FCFA',
            'deposit_note' => 'Solde à régler selon les modalités convenues.',
        ]);

        $response = $this->get(route('inscription.show', $form));

        $response->assertOk();
        $response->assertSee('Paiement intégral');
        $response->assertSee('200 000 FCFA');
        $response->assertSee('à partir de 50 000 FCFA');
        $response->assertSee('Solde à régler selon les modalités convenues.');
        $response->assertSee('Réservez votre place', false);
    }

    public function test_no_deposit_option_when_not_configured(): void
    {
        $form = RegistrationForm::create([
            'title' => 'Sans réservation', 'status' => 'publiee', 'price_amount' => '200 000 FCFA',
        ]);

        $response = $this->get(route('inscription.show', $form));

        $response->assertOk();
        $response->assertDontSee('Réservez votre place');
        $response->assertSee('Paiement intégral');
    }

    public function test_unpublished_form_is_not_accessible(): void
    {
        $form = RegistrationForm::create(['title' => 'Brouillon', 'status' => 'brouillon']);

        $this->get(route('inscription.show', $form))->assertNotFound();
    }

    public function test_submitting_the_form_stores_a_lead_and_notifies_admin(): void
    {
        Mail::fake();

        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $response = $this->post(route('inscription.store', $form), $this->validPayload([
            'motivations' => ['Améliorer mes compétences professionnelles'],
        ]));

        $response->assertRedirect(route('inscription.show', $form));
        $response->assertSessionHas('registration_sent');

        $this->assertDatabaseHas('registration_leads', [
            'registration_form_id' => $form->id,
            'email' => 'bertin@example.ci',
            'status' => 'nouveau',
        ]);

        Mail::assertSent(RegistrationLeadMail::class, fn ($mail) => $mail->hasCc('admin@roidumanioc.ci'));
    }

    public function test_submitting_the_same_email_twice_on_the_same_form_is_rejected(): void
    {
        Mail::fake();

        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $this->post(route('inscription.store', $form), $this->validPayload())
            ->assertRedirect(route('inscription.show', $form));

        $this->post(route('inscription.store', $form), $this->validPayload(['phone_1' => '0711111111', 'whatsapp' => '0711111111']))
            ->assertSessionHasErrors(['email' => 'Vous êtes déjà inscrit(e) avec cette adresse e-mail.']);

        $this->assertSame(1, RegistrationLead::count());
    }

    public function test_submitting_the_same_phone_number_twice_on_the_same_form_is_rejected(): void
    {
        Mail::fake();

        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $this->post(route('inscription.store', $form), $this->validPayload())
            ->assertRedirect(route('inscription.show', $form));

        // Même numéro que le premier envoi, mais écrit avec l'indicatif international
        // et des séparateurs — doit quand même être détecté (PhoneNumber::normalize()).
        $this->post(route('inscription.store', $form), $this->validPayload([
            'email' => 'autre@example.ci',
            'phone_1' => '+225 07 00 00 00 00',
        ]))->assertSessionHasErrors(['phone_1' => 'Vous êtes déjà inscrit(e) avec ce numéro de téléphone.']);

        $this->assertSame(1, RegistrationLead::count());
    }

    public function test_the_same_person_can_register_to_two_different_forms(): void
    {
        Mail::fake();

        $formA = RegistrationForm::create(['title' => 'Campagne A', 'status' => 'publiee']);
        $formB = RegistrationForm::create(['title' => 'Campagne B', 'status' => 'publiee']);

        $this->post(route('inscription.store', $formA), $this->validPayload())
            ->assertRedirect(route('inscription.show', $formA));

        $this->post(route('inscription.store', $formB), $this->validPayload())
            ->assertRedirect(route('inscription.show', $formB));

        $this->assertSame(2, RegistrationLead::count());
    }

    public function test_submission_requires_a_whatsapp_number(): void
    {
        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $overrides = $this->validPayload();
        unset($overrides['whatsapp']);

        $this->post(route('inscription.store', $form), $overrides)
            ->assertSessionHasErrors(['whatsapp']);

        $this->assertSame(0, RegistrationLead::count());
    }

    public function test_submission_without_an_email_is_accepted(): void
    {
        Mail::fake();

        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $overrides = $this->validPayload();
        unset($overrides['email']);

        $this->post(route('inscription.store', $form), $overrides)
            ->assertRedirect(route('inscription.show', $form));

        $this->assertDatabaseHas('registration_leads', [
            'registration_form_id' => $form->id,
            'email' => null,
        ]);
    }

    public function test_submission_with_other_status_function_stores_the_free_text_value(): void
    {
        Mail::fake();

        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $this->post(route('inscription.store', $form), $this->validPayload([
            'profession' => 'Autre',
            'profession_other' => 'Vétérinaire',
        ]))->assertRedirect(route('inscription.show', $form));

        $this->assertDatabaseHas('registration_leads', [
            'registration_form_id' => $form->id,
            'profession' => 'Vétérinaire',
        ]);
    }

    public function test_submission_validates_required_fields(): void
    {
        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $this->post(route('inscription.store', $form), [])
            ->assertSessionHasErrors(['gender', 'last_name', 'first_name', 'phone_1', 'whatsapp', 'experience_level', 'profession', 'age_range', 'city_country', 'payment_method']);

        $this->assertSame(0, RegistrationLead::count());
    }

    public function test_submission_rejects_honeypot(): void
    {
        $form = RegistrationForm::create(['title' => 'Directeur Commercial', 'status' => 'publiee']);

        $this->post(route('inscription.store', $form), $this->validPayload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, RegistrationLead::count());
    }

    public function test_cannot_submit_to_an_unpublished_form(): void
    {
        $form = RegistrationForm::create(['title' => 'Brouillon', 'status' => 'brouillon']);

        $this->post(route('inscription.store', $form), $this->validPayload())->assertNotFound();
        $this->assertSame(0, RegistrationLead::count());
    }
}
