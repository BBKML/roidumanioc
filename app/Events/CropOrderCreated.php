<?php

namespace App\Events;

use App\Models\CropOrder;

class CropOrderCreated
{
    public function __construct(public CropOrder $cropOrder) {}
}
