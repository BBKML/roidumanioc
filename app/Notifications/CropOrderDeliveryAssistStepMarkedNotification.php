<?php

namespace App\Notifications;

use App\Enums\DeliveryAssistStatus;
use App\Models\CropOrder;
use Illuminate\Notifications\Notification;

class CropOrderDeliveryAssistStepMarkedNotification extends Notification
{
    public function __construct(public CropOrder $cropOrder, public DeliveryAssistStatus $to) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'crop_order_delivery_assist_step_marked',
            'title' => 'Livraison : '.$this->to->label(),
            'message' => sprintf(
                'La livraison de « %s » est passée au statut « %s ».',
                $this->cropOrder->product_name,
                $this->to->label(),
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
