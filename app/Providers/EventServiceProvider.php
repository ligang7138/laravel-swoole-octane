<?php

namespace App\Providers;

use App\Domain\Delivery\Events\DeliveryAssigned;
use App\Domain\Delivery\Events\DeliveryCompleted;
use App\Domain\Order\Events\OrderCancelled;
use App\Domain\Order\Events\OrderCreated;
use App\Domain\Order\Events\OrderPaid;
use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\Payment\Events\PaymentSucceeded;
use App\Listeners\Order\HandleOrderCancellation;
use App\Listeners\Order\InitiateDelivery;
use App\Listeners\Order\SendOrderCreatedNotification;
use App\Listeners\Payment\RecordPaymentSuccess;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            SendOrderCreatedNotification::class,
        ],
        OrderPaid::class => [
            InitiateDelivery::class,
        ],
        OrderCancelled::class => [
            HandleOrderCancellation::class,
        ],
        PaymentSucceeded::class => [
            RecordPaymentSuccess::class,
        ],
    ];
}
