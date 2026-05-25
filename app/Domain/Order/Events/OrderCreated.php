<?php

namespace App\Domain\Order\Events;

use App\Domain\Order\Models\Order;

class OrderCreated
{
    public function __construct(
        public readonly Order $order,
    ) {}

    public function getOrderNo(): string
    {
        return $this->order->order_no;
    }

    public function getMerchantId(): int
    {
        return $this->order->merchant_id;
    }

    public function getCustomerId(): int
    {
        return $this->order->customer_id;
    }
}
