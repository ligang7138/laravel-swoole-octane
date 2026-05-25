<?php

namespace App\Domain\Delivery\Events;

use App\Domain\Delivery\Models\Delivery;

class DeliveryCompleted
{
    public function __construct(
        public readonly Delivery $delivery,
    ) {}
}
