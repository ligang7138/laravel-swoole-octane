<?php

namespace App\Infrastructure\Payment\Contracts;

interface PaymentGatewayInterface
{
    public function createPayment(string $orderNo, int $amount, string $channel, array $options = []): array;

    public function queryPayment(string $paymentNo): array;

    public function handleCallback(string $channel, array $data): array;

    public function refund(string $paymentNo, int $amount, string $reason = ''): array;
}
