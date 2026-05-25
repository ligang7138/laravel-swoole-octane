<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Repositories\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function findById(int $id): ?Order
    {
        return Order::with(['items', 'merchant', 'shop', 'customer'])->find($id);
    }

    public function findByOrderNo(string $orderNo): ?Order
    {
        return Order::with(['items', 'merchant', 'shop', 'customer'])
            ->where('order_no', $orderNo)
            ->first();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh();
    }

    public function paginateByCustomerId(int $customerId, int $perPage = 15)
    {
        return Order::with(['items', 'merchant', 'shop'])
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function paginateByMerchantId(int $merchantId, int $perPage = 15)
    {
        return Order::with(['items', 'customer'])
            ->where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
