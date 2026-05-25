<?php

namespace App\Listeners\Payment;

use App\Domain\Payment\Events\PaymentSucceeded;
use Illuminate\Support\Facades\Log;

class RecordPaymentSuccess
{
    public function handle(PaymentSucceeded $event): void
    {
        Log::info('PaymentSucceeded listener', [
            'payment_no' => $event->getPaymentNo(),
            'order_no' => $event->getOrderNo(),
        ]);
    }
}
