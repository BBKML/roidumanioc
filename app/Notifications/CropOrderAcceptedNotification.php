<?php

namespace App\Notifications;

use App\Models\CropOrder;
use Illuminate\Notifications\Notification;

class CropOrderAcceptedNotification extends Notification
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
            'type' => 'crop_order_accepted',
            'title' => 'Commande acceptée',
            'message' => sprintf(
                '%s a accepté votre commande « %s ». Les conditions de livraison arrivent bientôt.',
                $this->cropOrder->producerProfile->business_name,
                $this->cropOrder->product_name,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
