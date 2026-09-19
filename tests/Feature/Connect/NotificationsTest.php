<?php

namespace Tests\Feature\Connect;

use App\Actions\CreateConnectionRequest;
use App\Enums\CollaborationDeliveryStatus;
use App\Livewire\Connect\Conversation as ConversationComponent;
use App\Livewire\Connect\ReviewForm;
use App\Livewire\Connect\Show as ConnectShow;
use App\Livewire\Learner\NotificationBell;
use App\Mail\CollaborationConfirmedMail;
use App\Mail\CollaborationPaymentDeclaredMail;
use App\Mail\NewConnectionRequestMail;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Couvre les 8 événements de la Phase 9 (§24) : chaque transition doit créer la bonne
 * DatabaseNotification pour le bon destinataire (celui qui n'a pas agi), et les 3
 * événements à fort enjeu doivent en plus partir par e-mail — sans jamais bloquer
 * l'action métier si le SMTP tombe, et sans jamais exposer de coordonnée personnelle de
 * l'autre partie dans le corps de l'e-mail.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected CropOffer $offer;

    protected User $buyerUser;

    protected BuyerProfile $buyerProfile;

    protected ConnectionRequest $connectionRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producerUser = User::factory()->create(['name' => 'Producteur Kouassi']);
        $this->producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $this->offer = CropOffer::create([
            'producer_profile_id' => $this->producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create(['name' => 'Acheteuse Traore']);
        $this->buyerProfile = BuyerProfile::create([
            'user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);
    }

    /** Fait avancer la demande jusqu'à `proposition` (accept + negociation), acteur = producteur pour propose(). */
    protected function createRequestUpToNegotiation(): void
    {
        $this->connectionRequest = app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour, intéressé.');
        $this->connectionRequest->accept($this->producerUser);
        $this->connectionRequest->moveToNegotiation($this->buyerUser);
    }

    protected function confirmAndGetCollaboration(): Collaboration
    {
        $this->createRequestUpToNegotiation();
        $this->connectionRequest->propose($this->producerUser);

        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('confirmCollaboration');

        return Collaboration::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();
    }

    /**
     * Chaque étape du chemin nominal déclenche sa propre notification, donc un utilisateur
     * accumule plusieurs notifications au fil du scénario — on vérifie qu'une notification
     * du bon TYPE existe, plutôt que de compter le total (fragile face à l'accumulation).
     */
    protected function notificationsOfType(User $user, string $type): int
    {
        return $user->notifications->filter(fn ($n) => $n->data['type'] === $type)->count();
    }

    /* ------------------------------------------------------------------ *
     |  1. Nouvelle demande — fort enjeu (in-app + e-mail)
     * ------------------------------------------------------------------ */

    public function test_new_connection_request_notifies_the_producer_in_app_and_by_mail(): void
    {
        Mail::fake();

        $connectionRequest = app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour, intéressé.');

        $this->producerUser->refresh();
        $this->assertSame(1, $this->producerUser->notifications()->count());

        $notification = $this->producerUser->notifications()->first();
        $this->assertSame('connection_request_created', $notification->data['type']);
        $this->assertStringContainsString('Acheteuse Traore', $notification->data['message']);
        $this->assertSame(route('learner.requests.show', $connectionRequest), $notification->data['url']);

        Mail::assertSent(NewConnectionRequestMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email)
            && $mail->connectionRequest->is($connectionRequest));

        // Le demandeur, lui, n'est pas notifié de sa propre action.
        $this->assertSame(0, $this->buyerUser->notifications()->count());
    }

    public function test_new_connection_request_mail_never_exposes_the_buyers_personal_contact(): void
    {
        $connectionRequest = app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour, intéressé.');

        $rendered = (new NewConnectionRequestMail($connectionRequest))->render();

        $this->assertStringNotContainsString($this->buyerUser->email, $rendered);
        $this->assertStringNotContainsString($this->buyerUser->phone, $rendered);
        $this->assertStringContainsString(route('learner.requests.show', $connectionRequest), $rendered);
    }

    public function test_a_failing_smtp_never_blocks_the_connection_request_creation(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP indisponible'));

        $connectionRequest = app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour, intéressé.');

        $this->assertNotNull($connectionRequest->id);
        $this->producerUser->refresh();
        $this->assertSame(1, $this->producerUser->notifications()->count());
    }

    /* ------------------------------------------------------------------ *
     |  2. Demande acceptée — in-app uniquement
     * ------------------------------------------------------------------ */

    public function test_accepted_notifies_the_requester_in_app_only(): void
    {
        Mail::fake();

        $connectionRequest = app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour, intéressé.');
        $connectionRequest->accept($this->producerUser);

        $this->buyerUser->refresh();
        $this->assertSame(1, $this->notificationsOfType($this->buyerUser, 'connection_request_accepted'));

        // Seul l'e-mail de la création (fort enjeu) est parti ; accept() n'en envoie aucun.
        Mail::assertSentCount(1);
        Mail::assertSent(NewConnectionRequestMail::class);
    }

    /* ------------------------------------------------------------------ *
     |  3. Proposition reçue — in-app uniquement, notifie l'autre partie
     * ------------------------------------------------------------------ */

    public function test_proposed_notifies_the_other_party_not_the_actor(): void
    {
        Mail::fake();

        $this->createRequestUpToNegotiation();
        $this->connectionRequest->propose($this->producerUser);

        $this->buyerUser->refresh();
        $this->producerUser->refresh();

        $this->assertSame(1, $this->notificationsOfType($this->buyerUser, 'connection_request_proposed'));
        $this->assertSame(0, $this->notificationsOfType($this->producerUser, 'connection_request_proposed'));

        // moveToNegotiation()/propose() ne déclenchent aucun e-mail ; seule la création en a un.
        Mail::assertSentCount(1);
        Mail::assertSent(NewConnectionRequestMail::class);
    }

    /* ------------------------------------------------------------------ *
     |  4. Collaboration confirmée — fort enjeu (in-app + e-mail)
     * ------------------------------------------------------------------ */

    public function test_confirmed_notifies_the_other_party_in_app_and_by_mail(): void
    {
        Mail::fake();

        $this->createRequestUpToNegotiation();
        $this->connectionRequest->propose($this->producerUser);

        // Le producteur a proposé ; c'est lui qui confirme ici aussi (les deux parties
        // peuvent confirmer) — l'acheteur, qui n'a pas agi, doit être notifié.
        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('confirmCollaboration');

        $this->buyerUser->refresh();
        $this->assertSame(1, $this->notificationsOfType($this->buyerUser, 'connection_request_confirmed'));

        Mail::assertSent(CollaborationConfirmedMail::class, fn ($mail) => $mail->hasTo($this->buyerUser->email));
    }

    /* ------------------------------------------------------------------ *
     |  5. Paiement déclaré — fort enjeu (in-app + e-mail), toujours vers le producteur
     * ------------------------------------------------------------------ */

    public function test_payment_declared_notifies_the_producer_in_app_and_by_mail(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        Mail::fake();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        $this->producerUser->refresh();
        $this->assertSame(1, $this->notificationsOfType($this->producerUser, 'collaboration_payment_declared'));

        Mail::assertSent(CollaborationPaymentDeclaredMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_payment_declared_mail_never_exposes_personal_contact(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        $rendered = (new CollaborationPaymentDeclaredMail($collaboration))->render();

        $this->assertStringNotContainsString($this->buyerUser->email, $rendered);
        $this->assertStringNotContainsString($this->buyerUser->phone, $rendered);
    }

    /* ------------------------------------------------------------------ *
     |  6. Étape de livraison franchie — in-app uniquement, l'autre partie
     * ------------------------------------------------------------------ */

    public function test_delivery_step_notifies_the_other_party(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');
        $collaboration->confirmPayment($this->producerUser);

        Mail::fake();
        // Le producteur marque "en_cours" -> l'acheteur (qui n'a pas agi) est notifié.
        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours);

        $this->buyerUser->refresh();
        $this->producerUser->refresh();
        $this->assertSame(1, $this->notificationsOfType($this->buyerUser, 'collaboration_delivery_step_marked'));
        $this->assertSame(0, $this->notificationsOfType($this->producerUser, 'collaboration_delivery_step_marked'));

        Mail::assertNothingSent();
    }

    /* ------------------------------------------------------------------ *
     |  7. Nouvelle évaluation — in-app uniquement, vers l'évalué
     * ------------------------------------------------------------------ */

    public function test_review_submitted_notifies_the_ratee(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');
        $collaboration->confirmPayment($this->producerUser);
        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours);
        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::Effectuee);
        $collaboration->markDeliveryStep($this->buyerUser, CollaborationDeliveryStatus::Receptionnee);
        $collaboration->refresh();

        Mail::fake();

        Livewire::actingAs($this->buyerUser)->test(ReviewForm::class, ['collaboration' => $collaboration])
            ->set('rating', 5)
            ->set('criteria.qualite', 5)
            ->set('criteria.quantite', 4)
            ->set('criteria.respect_engagements', 5)
            ->set('criteria.ponctualite', 4)
            ->set('criteria.communication', 5)
            ->set('comment', 'Très bon producteur.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->producerUser->refresh();
        $this->assertSame(1, $this->notificationsOfType($this->producerUser, 'review_submitted'));

        Mail::assertNothingSent();
    }

    /* ------------------------------------------------------------------ *
     |  8. Nouveau message — in-app UNIQUEMENT, jamais d'e-mail
     * ------------------------------------------------------------------ */

    public function test_conversation_message_notifies_the_other_party_in_app_only(): void
    {
        $connectionRequest = app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour.');

        Mail::fake();

        Livewire::actingAs($this->buyerUser)->test(ConversationComponent::class, ['connectionRequest' => $connectionRequest])
            ->set('body', 'Bonjour, votre offre m\'intéresse.')
            ->call('send')
            ->assertHasNoErrors();

        $this->producerUser->refresh();
        $this->assertSame(1, $this->notificationsOfType($this->producerUser, 'conversation_message_sent'));

        Mail::assertNothingSent();

        // La réponse du producteur notifie l'acheteuse, pas lui-même.
        Livewire::actingAs($this->producerUser)->test(ConversationComponent::class, ['connectionRequest' => $connectionRequest])
            ->set('body', 'Oui, disponible.')
            ->call('send')
            ->assertHasNoErrors();

        $this->buyerUser->refresh();
        $this->producerUser->refresh();
        $this->assertSame(1, $this->notificationsOfType($this->buyerUser, 'conversation_message_sent'));
        $this->assertSame(
            1,
            $this->notificationsOfType($this->producerUser, 'conversation_message_sent'),
            'le producteur ne se notifie pas lui-même en répondant'
        );
    }

    /* ------------------------------------------------------------------ *
     |  Cloche (composant Livewire)
     * ------------------------------------------------------------------ */

    public function test_bell_shows_the_unread_count_and_marks_notifications_as_read(): void
    {
        app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour.');
        $this->producerUser->refresh();
        $notification = $this->producerUser->notifications()->first();

        Livewire::actingAs($this->producerUser)->test(NotificationBell::class)
            ->assertViewHas('unreadCount', 1)
            ->call('markAsRead', $notification->id)
            ->assertViewHas('unreadCount', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_clears_every_unread_notification(): void
    {
        $requestA = app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour.');
        $requestA->accept($this->producerUser);
        $requestA->refresh();
        app(CreateConnectionRequest::class)
            ->handle($this->buyerUser, 'acheteur', CropOffer::create([
                'producer_profile_id' => $this->producerProfile->id, 'product_name' => 'Manioc séché',
                'quantity' => 5, 'unit' => 'kg', 'location' => 'Daloa',
                'status' => 'publiee', 'is_available' => true,
            ]), 'Autre demande.');

        $this->producerUser->refresh();
        $this->assertGreaterThanOrEqual(2, $this->producerUser->unreadNotifications()->count());

        Livewire::actingAs($this->producerUser)->test(NotificationBell::class)
            ->call('markAllAsRead')
            ->assertViewHas('unreadCount', 0);

        $this->assertSame(0, $this->producerUser->unreadNotifications()->count());
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $this->offer, 'Bonjour.');
        $this->producerUser->refresh();
        $notification = $this->producerUser->notifications()->first();

        Livewire::actingAs($this->buyerUser)->test(NotificationBell::class)
            ->call('markAsRead', $notification->id);

        $this->assertNull($notification->fresh()->read_at);
    }
}
