<?php

namespace App\Listeners\Order;

use App\Domain\Order\Events\OrderCancelled;
use App\Infrastructure\Messaging\Contracts\MessagePublisherInterface;
use Illuminate\Support\Facades\Log;

class HandleOrderCancellation
{
    public function __construct(
        private readonly MessagePublisherInterface $messagePublisher,
    ) {}

    public function handle(OrderCancelled $event): void
    {
        Log::info('OrderCancelled listener', ['order_no' => $event->getOrderNo()]);

        $this->messagePublisher->publish('order_cancelled', json_encode([
            'type' => 'order_cancelled',
            'order_no' => $event->getOrderNo(),
            'reason' => $event->reason,
        ]));
    }
}
