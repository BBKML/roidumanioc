<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\SiteContent;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_contact_page_renders(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('Écrivez-nous');
    }

    public function test_contact_form_stores_message_and_sends_email(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Kouadio B.',
            'email' => 'kouadio@example.ci',
            'phone' => '0700000000',
            'subject' => 'Formations',
            'message' => 'Bonjour, je souhaite des informations sur vos formations.',
        ]);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'kouadio@example.ci',
            'subject' => 'Formations',
            'status' => 'nouveau',
        ]);

        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasCc('admin@roidumanioc.ci'));
    }

    public function test_contact_form_does_not_duplicate_the_admin_as_a_cc_when_it_is_already_the_recipient(): void
    {
        Mail::fake();
        $pied = SiteContent::where('key', 'pied')->firstOrFail();
        $pied->update(['data' => array_merge($pied->data, ['email' => 'admin@roidumanioc.ci'])]);

        $this->post('/contact', [
            'name' => 'Kouadio B.',
            'email' => 'kouadio@example.ci',
            'message' => 'Bonjour, je souhaite des informations sur vos formations.',
        ]);

        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('admin@roidumanioc.ci') && empty($mail->cc));
    }

    public function test_contact_form_validates_input(): void
    {
        $before = ContactMessage::count();

        $this->post('/contact', ['name' => '', 'email' => 'nope', 'message' => 'court'])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame($before, ContactMessage::count());
    }

    public function test_contact_form_submissions_are_rate_limited(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Kouadio B.',
            'email' => 'kouadio@example.ci',
            'message' => 'Bonjour, je souhaite des informations sur vos formations.',
        ];

        // throttle:6,1 sur la route : 6 tentatives passent, la 7e est bloquée (429).
        for ($i = 0; $i < 6; $i++) {
            $this->post('/contact', $payload)->assertRedirect(route('contact'));
        }

        $this->post('/contact', $payload)->assertStatus(429);
    }

    public function test_contact_form_rejects_honeypot(): void
    {
        $before = ContactMessage::count();

        $this->post('/contact', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Ceci est un message de spam automatisé.',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame($before, ContactMessage::count());
        $this->assertDatabaseMissing('contact_messages', ['email' => 'bot@example.com']);
    }

    public function test_newsletter_subscription(): void
    {
        $this->post('/newsletter', ['email' => 'Fatou@Example.CI'])
            ->assertSessionHas('newsletter');

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'fatou@example.ci']);

        // Idempotent : pas de doublon.
        $this->post('/newsletter', ['email' => 'fatou@example.ci']);
        $this->assertSame(1, NewsletterSubscriber::where('email', 'fatou@example.ci')->count());
    }

    /* ------------------------------------------------------------------ *
     |  Carte de la page Contact (coordonnées pilotées par le CMS)
     * ------------------------------------------------------------------ */

    public function test_contact_page_shows_the_map_when_coordinates_are_configured(): void
    {
        $pied = SiteContent::where('key', 'pied')->firstOrFail();
        $pied->update(['data' => array_merge($pied->data, [
            'map_lat' => '6.8206', 'map_lng' => '-5.2767', 'map_zoom' => '15', 'hours' => 'Lun–Ven, 8h–17h',
        ])]);

        $response = $this->get('/contact');

        $response->assertOk()
            ->assertSee('id="contactMap"', false)
            ->assertSee('data-lat="6.8206"', false)
            ->assertSee('data-lng="-5.2767"', false)
            ->assertSee('Lun–Ven, 8h–17h');
    }

    public function test_contact_page_hides_the_map_when_no_coordinates_are_configured(): void
    {
        $pied = SiteContent::where('key', 'pied')->firstOrFail();
        $data = $pied->data;
        unset($data['map_lat'], $data['map_lng']);
        $pied->update(['data' => $data]);

        $this->get('/contact')
            ->assertOk()
            ->assertDontSee('id="contactMap"', false);
    }
}
