<?php

namespace App\Notifications;

use App\Models\CropOrder;
use App\Models\User;
use Illuminate\Notifications\Notification;

class CropOrderCancelledNotification extends Notification
{
    public function __construct(public CropOrder $cropOrder, public User $actor) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'crop_order_cancelled',
            'title' => 'Commande annulée',
            'message' => sprintf(
                '%s a annulé la commande « %s ».',
                $this->actor->name,
                $this->cropOrder->product_name,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
