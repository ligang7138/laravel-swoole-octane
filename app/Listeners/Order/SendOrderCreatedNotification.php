<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderCreated;
use App\Infrastructure\Messaging\Contracts\MessagePublisherInterface;
use Illuminate\Support\Facades\Log;

class SendOrderCreatedNotification
{
    public function __construct(
        private readonly MessagePublisherInterface $messagePublisher,
    ) {}

    public function handle(OrderCreated $event): void
    {
        Log::info('OrderCreated listener', ['order_no' => $event->getOrderNo()]);

        $this->messagePublisher->publish('order_notify', json_encode([
            'type' => 'order_created',
            'order_no' => $event->getOrderNo(),
            'merchant_id' => $event->getMerchantId(),
            'customer_id' => $event->getCustomerId(),
        ]));
    }
}
