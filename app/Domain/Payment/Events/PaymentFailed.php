<?php

namespace App\Domain\Payment\Events;

use App\Domain\Payment\Models\Payment;

class PaymentFailed
{
    public function __construct(
        public readonly Payment $payment,
    ) {}
}
