<?php

namespace App\Notifications;

use App\Models\CropOrder;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

class CropOrderDeliveryFeeProposedNotification extends Notification
{
    public function __construct(public CropOrder $cropOrder, public User $actor, public bool $isFirst) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $amount = Number::format((float) $this->cropOrder->latestDeliveryProposal()?->amount, 0).' FCFA';

        return [
            'type' => 'crop_order_delivery_fee_proposed',
            'title' => $this->isFirst ? 'Conditions de livraison reçues' : 'Nouvelle proposition de frais de livraison',
            'message' => sprintf(
                '%s propose %s de frais de livraison pour « %s ».',
                $this->actor->name,
                $amount,
                $this->cropOrder->product_name,
            ),
            'url' => route('learner.crop-orders.show', $this->cropOrder),
        ];
    }
}
