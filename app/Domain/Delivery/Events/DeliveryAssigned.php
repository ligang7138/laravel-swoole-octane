<?php

namespace App\Domain\Delivery\Events;

use App\Domain\Delivery\Models\Delivery;

class DeliveryAssigned
{
    public function __construct(
        public readonly Delivery $delivery,
    ) {}
}
