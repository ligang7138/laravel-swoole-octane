<?php

namespace App\Infrastructure\Delivery\Contracts;

interface DeliveryServiceInterface
{
    public function createDelivery(int $orderId, string $orderNo, int $merchantId, int $shopId, int $customerId, int $addressId): array;

    public function assignRider(int $deliveryId): array;

    public function updateStatus(int $deliveryId, string $status, array $extra = []): array;
}
