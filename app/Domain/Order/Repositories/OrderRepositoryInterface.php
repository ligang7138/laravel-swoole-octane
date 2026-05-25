<?php

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Models\Order;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    public function findByOrderNo(string $orderNo): ?Order;

    public function create(array $data): Order;

    public function update(Order $order, array $data): Order;

    public function paginateByCustomerId(int $customerId, int $perPage = 15);

    public function paginateByMerchantId(int $merchantId, int $perPage = 15);
}
