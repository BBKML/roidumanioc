<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Messages;
use App\Livewire\Admin\Newsletter;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class MessagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    private function message(array $overrides = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'name' => 'Yao N.', 'email' => 'yao@example.ci', 'phone' => '0700000000',
            'subject' => 'Boutures', 'message' => 'Bonjour, avez-vous des boutures disponibles ?',
        ], $overrides));
    }

    public function test_opening_a_message_marks_it_read(): void
    {
        $m = $this->message();
        $this->assertSame('nouveau', $m->status->value);

        Livewire::test(Messages::class)->call('open', $m->id);

        $this->assertSame('lu', $m->fresh()->status->value);
    }

    public function test_opening_an_already_handled_message_does_not_revert_its_status(): void
    {
        $m = $this->message();
        $m->markRead();
        $m->markHandled(User::factory()->admin()->create());
        $this->assertSame('traite', $m->fresh()->status->value);

        Livewire::test(Messages::class)->call('open', $m->id);

        $this->assertSame('traite', $m->fresh()->status->value);
    }

    public function test_replying_sends_an_email_and_marks_handled(): void
    {
        $m = $this->message();

        Livewire::test(Messages::class)
            ->call('open', $m->id)
            ->set('replyBody', 'Bonjour Yao, oui nous avons des boutures, contactez-nous.')
            ->call('reply', $m->id)
            ->assertHasNoErrors();

        Mail::assertSent(ContactReplyMail::class, fn ($mail) => $mail->hasTo('yao@example.ci'));

        $m->refresh();
        $this->assertSame('traite', $m->status->value);
        $this->assertNotNull($m->replied_at);
        $this->assertSame($this->admin->id, $m->handled_by);
    }

    public function test_spam_and_reopen(): void
    {
        $m = $this->message();

        Livewire::test(Messages::class)->call('markSpam', $m->id);
        $this->assertSame('spam', $m->fresh()->status->value);

        Livewire::test(Messages::class)->call('reopen', $m->id);
        $this->assertSame('lu', $m->fresh()->status->value);
    }

    public function test_internal_note_is_saved_and_not_sent(): void
    {
        $m = $this->message();

        Livewire::test(Messages::class)
            ->call('open', $m->id)
            ->set('note', 'Rappeler jeudi.')
            ->call('saveNote', $m->id);

        $this->assertSame('Rappeler jeudi.', $m->fresh()->admin_note);
        Mail::assertNothingSent();
    }

    public function test_messages_screen_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'apprenant']))
            ->get(route('admin.messages'))->assertForbidden();
    }

    /* -------------------- Infolettre -------------------- */

    public function test_admin_unsubscribes_and_resubscribes(): void
    {
        $sub = NewsletterSubscriber::create(['email' => 'a@example.ci', 'source' => 'footer']);

        Livewire::test(Newsletter::class)->call('unsubscribe', $sub->id);
        $this->assertNotNull($sub->fresh()->unsubscribed_at);

        Livewire::test(Newsletter::class)->call('resubscribe', $sub->id);
        $this->assertNull($sub->fresh()->unsubscribed_at);
    }

    public function test_newsletter_signup_reactivates_a_former_subscriber(): void
    {
        $sub = NewsletterSubscriber::create(['email' => 'b@example.ci', 'source' => 'footer', 'unsubscribed_at' => now()]);

        $this->post('/newsletter', ['email' => 'B@example.ci'])->assertSessionHas('newsletter');

        $this->assertNull($sub->fresh()->unsubscribed_at);
        $this->assertSame(1, NewsletterSubscriber::count());
    }

    public function test_csv_export_lists_active_subscribers_only(): void
    {
        NewsletterSubscriber::create(['email' => 'actif@example.ci', 'source' => 'footer']);
        NewsletterSubscriber::create(['email' => 'parti@example.ci', 'source' => 'footer', 'unsubscribed_at' => now()]);

        $res = $this->get(route('admin.newsletter.export'));
        $res->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $body = $res->streamedContent();
        $this->assertStringContainsString('actif@example.ci', $body);
        $this->assertStringNotContainsString('parti@example.ci', $body);
    }
}
