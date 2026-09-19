<?php

namespace Tests\Feature\CropOrder;

use App\Actions\CreateCropOrder;
use App\Enums\DeliveryAssistStatus;
use App\Mail\CropOrderConfirmedMail;
use App\Mail\CropOrderDeliveryConditionsRejectedMail;
use App\Mail\CropOrderDeliveryConditionsToReviewMail;
use App\Mail\CropOrderDeliveryFeeProposedMail;
use App\Mail\CropOrderRefusedMail;
use App\Mail\DeliveryDoneMail;
use App\Mail\NewCropOrderMail;
use App\Mail\NewDeliveryAssistRequestMail;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\CropOrder;
use App\Models\ProducerProfile;
use App\Models\User;
use App\Notifications\CropOrderAcceptedNotification;
use App\Notifications\CropOrderCreatedNotification;
use App\Notifications\CropOrderDeliveredNotification;
use App\Notifications\CropOrderSelfArrangedDeliveryDeclaredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CropOrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected CropOffer $offer;

    protected User $buyerUser;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producerUser = User::factory()->create();
        $this->producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $this->offer = CropOffer::create([
            'producer_profile_id' => $this->producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 100, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $this->admin = User::factory()->admin()->create();
    }

    protected function makeOrder(): CropOrder
    {
        return app(CreateCropOrder::class)->handle(
            $this->buyerUser, $this->offer, 10.0, 'kg', 'Cocody', null, null, 'Mobile Money', null, null,
        );
    }

    public function test_creating_an_order_notifies_the_producer_in_app_and_by_email(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();

        Notification::assertSentTo($this->producerUser, CropOrderCreatedNotification::class);
        Notification::assertNotSentTo($this->buyerUser, CropOrderCreatedNotification::class);
        Mail::assertSent(NewCropOrderMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_accepting_notifies_the_buyer_in_app_only(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        Notification::assertSentTo($this->buyerUser, CropOrderAcceptedNotification::class);
    }

    public function test_refusing_notifies_the_buyer_by_email_too(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->refuse($this->producerUser, 'Rupture de stock');

        Mail::assertSent(CropOrderRefusedMail::class, fn ($mail) => $mail->hasTo($this->buyerUser->email));
    }

    public function test_submitting_delivery_conditions_notifies_active_admins_by_email(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);

        Mail::assertSent(CropOrderDeliveryConditionsToReviewMail::class, fn ($mail) => $mail->hasTo($this->admin->email));
        // Rien n'est encore envoyé à l'acheteur — pas encore validé.
        Mail::assertNotSent(CropOrderDeliveryFeeProposedMail::class);
    }

    public function test_rejecting_delivery_conditions_notifies_the_producer(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->rejectDeliveryConditions($this->admin, 'Frais trop élevés');

        Mail::assertSent(CropOrderDeliveryConditionsRejectedMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_approving_delivery_conditions_notifies_the_buyer_only(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);

        Mail::assertSent(CropOrderDeliveryFeeProposedMail::class, fn ($mail) => $mail->hasTo($this->buyerUser->email));
        Mail::assertNotSent(CropOrderDeliveryFeeProposedMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_a_counter_proposal_notifies_the_other_party_only(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 5000);

        Mail::assertSent(CropOrderDeliveryFeeProposedMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_confirming_the_order_notifies_both_parties(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 5000);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->producerUser);

        Mail::assertSent(CropOrderConfirmedMail::class, fn ($mail) => $mail->hasTo($this->buyerUser->email));
        Mail::assertSent(CropOrderConfirmedMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_requesting_delivery_assistance_notifies_active_admins(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 5000);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->producerUser);
        $order = $order->fresh();
        $order->requestDeliveryAssistance($this->buyerUser);

        Mail::assertSent(NewDeliveryAssistRequestMail::class, fn ($mail) => $mail->hasTo($this->admin->email));
    }

    public function test_declaring_self_arranged_delivery_notifies_the_other_party_in_app_only(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 5000);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->producerUser);
        $order = $order->fresh();

        $order->declareSelfArrangedDelivery($this->buyerUser, 'acheteur');

        Notification::assertSentTo($this->producerUser, CropOrderSelfArrangedDeliveryDeclaredNotification::class);
        Notification::assertNotSentTo($this->buyerUser, CropOrderSelfArrangedDeliveryDeclaredNotification::class);
    }

    public function test_confirming_a_self_arranged_delivery_notifies_the_producer_by_email(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 5000);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->producerUser);
        $order = $order->fresh();
        $order->declareSelfArrangedDelivery($this->buyerUser, 'acheteur');
        $order = $order->fresh();

        $order->confirmSelfArrangedDelivery($this->buyerUser);

        Notification::assertSentTo($this->producerUser, CropOrderDeliveredNotification::class);
        Mail::assertSent(DeliveryDoneMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_only_the_final_delivered_step_sends_an_email(): void
    {
        Notification::fake();
        Mail::fake();

        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 5000);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->producerUser);
        $order = $order->fresh();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order = $order->fresh();

        $order->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::EnPreparation);
        Mail::assertNotSent(DeliveryDoneMail::class);

        $order->fresh()->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::LivreurContacte);
        $order->fresh()->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::EnCours);
        $order->fresh()->markDeliveryAssistStep($this->admin, DeliveryAssistStatus::Livree);

        Mail::assertSent(DeliveryDoneMail::class, fn ($mail) => $mail->hasTo($this->buyerUser->email));
        Mail::assertSent(DeliveryDoneMail::class, fn ($mail) => $mail->hasTo($this->producerUser->email));
    }

    public function test_no_email_is_sent_when_the_recipient_has_no_email_address(): void
    {
        Notification::fake();
        Mail::fake();

        $this->producerUser->update(['email' => null, 'phone' => '0700000001']);

        $order = $this->makeOrder();

        Mail::assertNothingSent();
        Notification::assertSentTo($this->producerUser, CropOrderCreatedNotification::class);
    }
}
