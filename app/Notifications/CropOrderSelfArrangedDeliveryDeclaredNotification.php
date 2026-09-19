<?php

namespace App\Notifications;

use App\Models\CropOrder;
use App\Models\User;
use Illuminate\Notifications\Notification;

class CropOrderSelfArrangedDeliveryDeclaredNotification extends Notification
{
    public function __construct(public CropOrder $cropOrder, public User $actor, public string $mode) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'crop_order_self_arranged_delivery_declared',
            'title' => 'Livraison auto-organisée',
            'message' => sprintf(
                '%s a indiqué que %s pour « %s », sans passer par l\'aide de l\'administration.',
                $this->actor->name,
                $this->mode === 'acheteur' ? 'il/elle récupère la marchandise lui/elle-même' : 'il/elle livre lui/elle-même',
                $this->cropOrder->product_name,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
