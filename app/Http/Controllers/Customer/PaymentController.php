<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreatePaymentRequest;
use App\Http\Resources\Payment\PaymentResource;
use App\Services\Payment\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function store(CreatePaymentRequest $request)
    {
        $payment = $this->paymentService->createPayment(
            $request->order_id,
            $request->channel,
        );
        return new PaymentResource($payment);
    }

    public function callback(string $channel)
    {
        $data = $channel === 'wechat' ? request()->all() : request()->all();
        $payment = $this->paymentService->handleCallback($channel, $data);
        return new PaymentResource($payment);
    }
}
