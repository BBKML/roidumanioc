<?php

namespace App\Notifications;

use App\Models\CropOrder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class CropOrderConfirmedNotification extends Notification
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
            'type' => 'crop_order_confirmed',
            'title' => 'Commande confirmée',
            'message' => sprintf(
                'Accord trouvé pour « %s » — total %s. Les conditions sont figées.',
                $this->cropOrder->product_name,
                Number::format((float) $this->cropOrder->total_amount, 0).' FCFA',
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
