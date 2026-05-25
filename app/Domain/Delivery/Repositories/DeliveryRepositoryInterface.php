<?php

namespace App\Domain\Delivery\Repositories;

use App\Domain\Delivery\Models\Delivery;

interface DeliveryRepositoryInterface
{
    public function findById(int $id): ?Delivery;

    public function findByOrderNo(string $orderNo): ?Delivery;

    public function create(array $data): Delivery;

    public function update(Delivery $delivery, array $data): Delivery;

    public function findPendingByRiderId(int $riderId);
}
