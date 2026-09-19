<?php

namespace App\Notifications;

use App\Models\CropOrder;
use Illuminate\Notifications\Notification;

class CropOrderRefusedNotification extends Notification
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
            'type' => 'crop_order_refused',
            'title' => 'Commande refusée',
            'message' => sprintf(
                '%s a refusé votre commande « %s ».',
                $this->cropOrder->producerProfile->business_name,
                $this->cropOrder->product_name,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
