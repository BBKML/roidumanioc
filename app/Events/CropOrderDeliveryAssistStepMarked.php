<?php

namespace App\Events;

use App\Enums\DeliveryAssistStatus;
use App\Models\CropOrder;
use App\Models\User;

class CropOrderDeliveryAssistStepMarked
{
    public function __construct(public CropOrder $cropOrder, public User $admin, public DeliveryAssistStatus $to) {}
}
