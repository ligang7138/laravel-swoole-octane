<?php

namespace App\Infrastructure\Delivery\Internal;

use App\Infrastructure\Delivery\Contracts\DeliveryServiceInterface;
use Illuminate\Support\Facades\Log;

class InternalDeliveryService implements DeliveryServiceInterface
{
    public function createDelivery(int $orderId, string $orderNo, int $merchantId, int $shopId, int $customerId, int $addressId): array
    {
        $deliveryNo = 'DLV' . date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        Log::info('Internal delivery created', [
            'delivery_no' => $deliveryNo,
            'order_no' => $orderNo,
        ]);

        return [
            'delivery_no' => $deliveryNo,
            'order_id' => $orderId,
            'status' => 'pending',
        ];
    }

    public function assignRider(int $deliveryId): array
    {
        Log::info('Internal delivery rider assigned', ['delivery_id' => $deliveryId]);

        return [
            'delivery_id' => $deliveryId,
            'rider_id' => random_int(1, 100),
            'status' => 'assigned',
        ];
    }

    public function updateStatus(int $deliveryId, string $status, array $extra = []): array
    {
        Log::info('Internal delivery status updated', [
            'delivery_id' => $deliveryId,
            'status' => $status,
        ]);

        return [
            'delivery_id' => $deliveryId,
            'status' => $status,
        ];
    }
}
