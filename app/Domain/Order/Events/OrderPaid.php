<?php

namespace App\Domain\Order\Events;

use App\Domain\Order\Models\Order;

class OrderPaid
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
}
