<?php

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Models\OrderItem;

interface OrderItemRepositoryInterface
{
    public function create(array $data): OrderItem;

    public function createBatch(array $items): array;

    public function findByOrderId(int $orderId): array;
}
