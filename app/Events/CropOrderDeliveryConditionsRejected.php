<?php

namespace App\Events;

use App\Models\CropOrder;
use App\Models\User;

class CropOrderDeliveryConditionsRejected
{
    public function __construct(public CropOrder $cropOrder, public User $admin, public string $reason) {}
}
