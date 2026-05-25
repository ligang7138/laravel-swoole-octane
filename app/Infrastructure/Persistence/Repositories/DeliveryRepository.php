<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Delivery\Models\Delivery;
use App\Domain\Delivery\Repositories\DeliveryRepositoryInterface;

class DeliveryRepository implements DeliveryRepositoryInterface
{
    public function findById(int $id): ?Delivery
    {
        return Delivery::find($id);
    }

    public function findByOrderNo(string $orderNo): ?Delivery
    {
        return Delivery::where('order_no', $orderNo)->first();
    }

    public function create(array $data): Delivery
    {
        return Delivery::create($data);
    }

    public function update(Delivery $delivery, array $data): Delivery
    {
        $delivery->update($data);
        return $delivery->fresh();
    }

    public function findPendingByRiderId(int $riderId)
    {
        return Delivery::where('rider_id', $riderId)
            ->whereIn('status', [Delivery::STATUS_ASSIGNED, Delivery::STATUS_PICKED_UP, Delivery::STATUS_DELIVERING])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
