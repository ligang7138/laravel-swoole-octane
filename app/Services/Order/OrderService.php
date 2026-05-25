<?php

namespace App\Services\Order;

use App\Domain\Order\Exceptions\OrderStateException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Repositories\OrderItemRepositoryInterface;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Infrastructure\Lock\Contracts\LockServiceInterface;
use App\Infrastructure\Messaging\Contracts\MessagePublisherInterface;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly OrderItemRepositoryInterface $orderItemRepo,
        private readonly ProductRepositoryInterface $productRepo,
        private readonly LockServiceInterface $lockService,
        private readonly MessagePublisherInterface $messagePublisher,
    ) {}

    public function createOrder(int $customerId, int $merchantId, int $shopId, int $addressId, array $items, string $remark = ''): Order
    {
        return $this->lockService->runWithLock("order:create:{$customerId}", 5, function () use ($customerId, $merchantId, $shopId, $addressId, $items, $remark) {
            return DB::transaction(function () use ($customerId, $merchantId, $shopId, $addressId, $items, $remark) {
                $totalAmount = 0;
                $orderItems = [];

                foreach ($items as $item) {
                    $product = $this->productRepo->findOnSaleById($item['product_id']);
                    if (!$product) {
                        throw new \RuntimeException("商品 [{$item['product_id']}] 不存在或已下架");
                    }

                    $sku = $product->skus->firstWhere('id', $item['sku_id']);
                    if (!$sku || !$sku->isOnSale()) {
                        throw new \RuntimeException("SKU [{$item['sku_id']}] 不存在或已下架");
                    }

                    if (!$sku->hasStock($item['quantity'])) {
                        throw new \RuntimeException("SKU [{$sku->name}] 库存不足");
                    }

                    $sku->decrementStock($item['quantity']);

                    $itemTotal = $sku->price * $item['quantity'];
                    $totalAmount += $itemTotal;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'sku_id' => $sku->id,
                        'product_name' => $product->name,
                        'sku_name' => $sku->name,
                        'image' => $product->image,
                        'price' => $sku->price,
                        'quantity' => $item['quantity'],
                        'total_amount' => $itemTotal,
                    ];
                }

                $order = $this->orderRepo->create([
                    'order_no' => Order::generateOrderNo(),
                    'type' => Order::TYPE_NORMAL,
                    'status' => Order::STATUS_PENDING,
                    'merchant_id' => $merchantId,
                    'shop_id' => $shopId,
                    'customer_id' => $customerId,
                    'address_id' => $addressId,
                    'total_amount' => $totalAmount,
                    'pay_amount' => $totalAmount,
                    'discount_amount' => 0,
                    'delivery_fee' => 0,
                    'remark' => $remark,
                ]);

                foreach ($orderItems as &$orderItem) {
                    $orderItem['order_id'] = $order->id;
                }
                $this->orderItemRepo->createBatch($orderItems);

                // 发布订单创建消息
                $this->messagePublisher->publish('order_created', json_encode([
                    'order_no' => $order->order_no,
                    'customer_id' => $customerId,
                    'merchant_id' => $merchantId,
                    'total_amount' => $totalAmount,
                ]));

                return $order;
            });
        });
    }

    public function cancelOrder(int $orderId, int $customerId, string $reason = ''): Order
    {
        $order = $this->orderRepo->findById($orderId);

        if (!$order || $order->customer_id !== $customerId) {
            throw new \RuntimeException('订单不存在');
        }

        if (!$order->canCancel()) {
            throw OrderStateException::cannotCancel($order->status);
        }

        return DB::transaction(function () use ($order, $reason) {
            // 恢复库存
            foreach ($order->items as $item) {
                if ($item->sku_id) {
                    $item->sku()->increment('stock', $item->quantity);
                }
            }

            $order->markAsCancelled($reason);

            $this->messagePublisher->publish('order_cancelled', json_encode([
                'order_no' => $order->order_no,
                'reason' => $reason,
            ]));

            return $order->fresh();
        });
    }

    public function getOrder(int $orderId): ?Order
    {
        return $this->orderRepo->findById($orderId);
    }

    public function getCustomerOrders(int $customerId, int $perPage = 15)
    {
        return $this->orderRepo->paginateByCustomerId($customerId, $perPage);
    }

    public function getMerchantOrders(int $merchantId, int $perPage = 15)
    {
        return $this->orderRepo->paginateByMerchantId($merchantId, $perPage);
    }
}
