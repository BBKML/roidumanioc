<?php

namespace App\Notifications;

use App\Models\CropOrder;
use Illuminate\Notifications\Notification;

class CropOrderDeliveredNotification extends Notification
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
            'type' => 'crop_order_delivered',
            'title' => 'Commande livrée',
            'message' => sprintf(
                "L'acheteur a confirmé avoir reçu « %s ». Commande terminée.",
                $this->cropOrder->product_name,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
