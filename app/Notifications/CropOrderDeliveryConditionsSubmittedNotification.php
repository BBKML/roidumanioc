<?php

namespace App\Notifications;

use App\Models\CropOrder;
use Illuminate\Notifications\Notification;

class CropOrderDeliveryConditionsSubmittedNotification extends Notification
{
    public function __construct(public CropOrder $cropOrder) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'crop_order_delivery_conditions_submitted',
            'title' => 'Conditions de livraison à valider',
            'message' => sprintf(
                '%s a soumis ses conditions de livraison pour « %s » — en attente de validation.',
                $this->cropOrder->producerProfile->business_name,
                $this->cropOrder->product_name,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
