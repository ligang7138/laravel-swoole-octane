<?php

namespace App\Domain\Payment\Events;

use App\Domain\Payment\Models\Payment;

class PaymentSucceeded
{
    public function __construct(
        public readonly Payment $payment,
    ) {}

    public function getPaymentNo(): string
    {
        return $this->payment->payment_no;
    }

    public function getOrderNo(): string
    {
        return $this->payment->order_no;
    }
}
