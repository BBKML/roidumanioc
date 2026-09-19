<?php

namespace App\Notifications;

use App\Models\CropOrder;
use Illuminate\Notifications\Notification;

class CropOrderDeliveryConditionsRejectedNotification extends Notification
{
    public function __construct(public CropOrder $cropOrder, public string $reason) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'crop_order_delivery_conditions_rejected',
            'title' => 'Conditions de livraison renvoyées',
            'message' => sprintf(
                "L'administration a renvoyé vos conditions de livraison pour « %s » : %s",
                $this->cropOrder->product_name,
                $this->reason,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
