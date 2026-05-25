<?php

namespace App\Domain\Order\Events;

use App\Domain\Order\Models\Order;

class OrderCancelled
{
    public function __construct(
        public readonly Order $order,
        public readonly string $reason = '',
    ) {}

    public function getOrderNo(): string
    {
        return $this->order->order_no;
    }
}
