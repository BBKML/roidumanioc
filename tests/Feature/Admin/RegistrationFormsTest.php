<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RegistrationForms;
use App\Models\RegistrationForm;
use App\Models\RegistrationLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationFormsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_the_list_shows_a_link_to_the_dedicated_builder_page(): void
    {
        $form = RegistrationForm::create(['title' => 'Test', 'slug' => 'test', 'status' => 'brouillon']);

        $this->get(route('admin.registration-forms'))
            ->assertOk()
            ->assertSee(route('admin.registration-forms.create'), false)
            ->assertSee(route('admin.registration-forms.edit', $form->slug), false);
    }

    public function test_toggle_publish(): void
    {
        $form = RegistrationForm::create(['title' => 'Test', 'slug' => 'test', 'status' => 'brouillon']);

        Livewire::test(RegistrationForms::class)->call('togglePublish', $form->id);

        $this->assertSame('publiee', $form->fresh()->status->value);
    }

    public function test_cannot_delete_a_form_that_already_has_leads(): void
    {
        $form = RegistrationForm::create(['title' => 'Test', 'slug' => 'test', 'status' => 'publiee']);
        RegistrationLead::create([
            'registration_form_id' => $form->id,
            'last_name' => 'Kouadio', 'first_name' => 'B.', 'email' => 'b@example.ci', 'phone_1' => '07',
        ]);

        Livewire::test(RegistrationForms::class)->call('delete', $form->id);

        $this->assertNotNull($form->fresh());
    }

    public function test_guest_and_learner_cannot_access(): void
    {
        $this->post('/logout');
        $this->get(route('admin.registration-forms'))->assertRedirect(route('login'));

        $learner = User::factory()->create(['role' => 'apprenant']);
        $this->actingAs($learner)->get(route('admin.registration-forms'))->assertForbidden();
    }
}
