<?php

namespace App\Notifications;

use App\Models\CropOrder;
use Illuminate\Notifications\Notification;

class CropOrderCreatedNotification extends Notification
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
            'type' => 'crop_order_created',
            'title' => 'Nouvelle commande',
            'message' => sprintf(
                '%s souhaite commander « %s » (%s %s).',
                $this->cropOrder->buyerProfile->company_name ?: 'Un acheteur',
                $this->cropOrder->product_name,
                rtrim(rtrim(number_format((float) $this->cropOrder->requested_quantity, 2, '.', ' '), '0'), '.'),
                $this->cropOrder->requested_unit,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
