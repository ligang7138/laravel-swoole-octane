<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Order\Models\OrderItem;
use App\Domain\Order\Repositories\OrderItemRepositoryInterface;

class OrderItemRepository implements OrderItemRepositoryInterface
{
    public function create(array $data): OrderItem
    {
        return OrderItem::create($data);
    }

    public function createBatch(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = OrderItem::create($item);
        }
        return $result;
    }

    public function findByOrderId(int $orderId): array
    {
        return OrderItem::where('order_id', $orderId)->get()->all();
    }
}
