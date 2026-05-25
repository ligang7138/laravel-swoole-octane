<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment
    {
        return Payment::find($id);
    }

    public function findByPaymentNo(string $paymentNo): ?Payment
    {
        return Payment::where('payment_no', $paymentNo)->first();
    }

    public function findByOrderNo(string $orderNo): ?Payment
    {
        return Payment::where('order_no', $orderNo)->first();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);
        return $payment->fresh();
    }
}
