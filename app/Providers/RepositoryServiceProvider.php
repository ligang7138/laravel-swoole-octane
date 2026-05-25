<?php

namespace App\Providers;

use App\Domain\Delivery\Repositories\DeliveryRepositoryInterface;
use App\Domain\Merchant\Repositories\MerchantRepositoryInterface;
use App\Domain\Order\Repositories\OrderItemRepositoryInterface;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\User\Repositories\CustomerRepositoryInterface;
use App\Infrastructure\Cache\Contracts\CacheServiceInterface;
use App\Infrastructure\Cache\Redis\RedisCacheService;
use App\Infrastructure\Delivery\Contracts\DeliveryServiceInterface;
use App\Infrastructure\Delivery\Internal\InternalDeliveryService;
use App\Infrastructure\Lock\Contracts\LockServiceInterface;
use App\Infrastructure\Lock\Redis\RedisLockService;
use App\Infrastructure\Messaging\Contracts\MessagePublisherInterface;
use App\Infrastructure\Messaging\Kafka\KafkaPublisher;
use App\Infrastructure\Messaging\RocketMQ\RocketMQPublisher;
use App\Infrastructure\Payment\Alipay\AlipayGateway;
use App\Infrastructure\Payment\Contracts\PaymentGatewayInterface;
use App\Infrastructure\Payment\Wechat\WechatPayGateway;
use App\Infrastructure\Persistence\Repositories\CustomerRepository;
use App\Infrastructure\Persistence\Repositories\DeliveryRepository;
use App\Infrastructure\Persistence\Repositories\MerchantRepository;
use App\Infrastructure\Persistence\Repositories\OrderItemRepository;
use App\Infrastructure\Persistence\Repositories\OrderRepository;
use App\Infrastructure\Persistence\Repositories\PaymentRepository;
use App\Infrastructure\Persistence\Repositories\ProductRepository;
use App\Services\Payment\PaymentService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository 绑定
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderItemRepositoryInterface::class, OrderItemRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(DeliveryRepositoryInterface::class, DeliveryRepository::class);
        $this->app->bind(MerchantRepositoryInterface::class, MerchantRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);

        // Infrastructure 绑定
        $this->app->bind(CacheServiceInterface::class, RedisCacheService::class);
        $this->app->bind(LockServiceInterface::class, RedisLockService::class);
        $this->app->bind(DeliveryServiceInterface::class, InternalDeliveryService::class);

        // 消息队列绑定（根据配置选择 RocketMQ 或 Kafka）
        $this->app->bind(MessagePublisherInterface::class, function ($app) {
            return match (config('mq.default', 'rocketmq')) {
                'kafka' => $app->make(KafkaPublisher::class),
                default => $app->make(RocketMQPublisher::class),
            };
        });

        // 支付网关注册
        $this->app->tag([
            WechatPayGateway::class,
            AlipayGateway::class,
        ], 'payment.gateways');

        $this->app->afterResolving(PaymentService::class, function (PaymentService $service) {
            $service->registerGateway('wechat', $this->app->make(WechatPayGateway::class));
            $service->registerGateway('alipay', $this->app->make(AlipayGateway::class));
        });
    }
}
