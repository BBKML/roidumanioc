<?php

namespace App\Events;

use App\Models\CropOrder;

class CropOrderDeliveryConditionsSubmittedForReview
{
    public function __construct(public CropOrder $cropOrder) {}
}
