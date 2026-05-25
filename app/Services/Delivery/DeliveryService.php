<?php

namespace App\Services\Delivery;

use App\Domain\Delivery\Models\Delivery;
use App\Domain\Delivery\Repositories\DeliveryRepositoryInterface;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Delivery\Contracts\DeliveryServiceInterface;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public function __construct(
        private readonly DeliveryRepositoryInterface $deliveryRepo,
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly DeliveryServiceInterface $deliveryProvider,
    ) {}

    public function createDelivery(int $orderId): Delivery
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order || !$order->canDeliver()) {
            throw new \RuntimeException('订单状态不允许创建配送');
        }

        return DB::transaction(function () use ($order) {
            $result = $this->deliveryProvider->createDelivery(
                $order->id,
                $order->order_no,
                $order->merchant_id,
                $order->shop_id,
                $order->customer_id,
                $order->address_id,
            );

            $delivery = $this->deliveryRepo->create([
                'delivery_no' => $result['delivery_no'],
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'merchant_id' => $order->merchant_id,
                'shop_id' => $order->shop_id,
                'customer_id' => $order->customer_id,
                'address_id' => $order->address_id,
                'status' => Delivery::STATUS_PENDING,
            ]);

            $order->markAsDelivering();

            return $delivery;
        });
    }

    public function assignRider(int $deliveryId): Delivery
    {
        $delivery = $this->deliveryRepo->findById($deliveryId);
        if (!$delivery || !$delivery->canAssign()) {
            throw new \RuntimeException('配送单状态不允许分配骑手');
        }

        $result = $this->deliveryProvider->assignRider($deliveryId);
        $delivery->assign($result['rider_id']);

        return $delivery->fresh();
    }

    public function completeDelivery(int $deliveryId): Delivery
    {
        $delivery = $this->deliveryRepo->findById($deliveryId);
        if (!$delivery) {
            throw new \RuntimeException('配送单不存在');
        }

        return DB::transaction(function () use ($delivery) {
            $delivery->markAsCompleted();

            $order = $this->orderRepo->findByOrderNo($delivery->order_no);
            if ($order && $order->canComplete()) {
                $order->markAsCompleted();
            }

            return $delivery->fresh();
        });
    }

    public function getDeliveryByOrderNo(string $orderNo): ?Delivery
    {
        return $this->deliveryRepo->findByOrderNo($orderNo);
    }
}
