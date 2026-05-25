<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Payment;

interface PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment;

    public function findByPaymentNo(string $paymentNo): ?Payment;

    public function findByOrderNo(string $orderNo): ?Payment;

    public function create(array $data): Payment;

    public function update(Payment $payment, array $data): Payment;
}
