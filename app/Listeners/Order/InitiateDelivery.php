<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderPaid;
use App\Services\Delivery\DeliveryService;
use Illuminate\Support\Facades\Log;

class InitiateDelivery
{
    public function __construct(
        private readonly DeliveryService $deliveryService,
    ) {}

    public function handle(OrderPaid $event): void
    {
        Log::info('OrderPaid listener - initiate delivery', ['order_no' => $event->getOrderNo()]);

        try {
            $this->deliveryService->createDelivery($event->order->id);
        } catch (\Throwable $e) {
            Log::error('Failed to initiate delivery', [
                'order_no' => $event->getOrderNo(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
