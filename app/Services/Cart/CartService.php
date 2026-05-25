<?php

namespace App\Services\Cart;

use App\Infrastructure\Cache\Contracts\CacheServiceInterface;

class CartService
{
    private const CACHE_TTL = 86400 * 7; // 7天

    public function __construct(
        private readonly CacheServiceInterface $cacheService,
    ) {}

    public function getCart(int $customerId): array
    {
        return $this->cacheService->get($this->key($customerId)) ?? [];
    }

    public function addItem(int $customerId, int $merchantId, int $shopId, int $productId, int $skuId, int $quantity, int $price): void
    {
        $cart = $this->getCart($customerId);

        $shopKey = "shop_{$shopId}";
        if (!isset($cart[$shopKey])) {
            $cart[$shopKey] = [
                'merchant_id' => $merchantId,
                'shop_id' => $shopId,
                'items' => [],
            ];
        }

        $itemKey = "{$productId}_{$skuId}";
        if (isset($cart[$shopKey]['items'][$itemKey])) {
            $cart[$shopKey]['items'][$itemKey]['quantity'] += $quantity;
        } else {
            $cart[$shopKey]['items'][$itemKey] = [
                'product_id' => $productId,
                'sku_id' => $skuId,
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        $this->cacheService->set($this->key($customerId), $cart, self::CACHE_TTL);
    }

    public function updateItemQuantity(int $customerId, int $productId, int $skuId, int $quantity): void
    {
        $cart = $this->getCart($customerId);
        $itemKey = "{$productId}_{$skuId}";

        foreach ($cart as $shopKey => &$shop) {
            if (isset($shop['items'][$itemKey])) {
                if ($quantity <= 0) {
                    unset($shop['items'][$itemKey]);
                    if (empty($shop['items'])) {
                        unset($cart[$shopKey]);
                    }
                } else {
                    $shop['items'][$itemKey]['quantity'] = $quantity;
                }
                break;
            }
        }

        $this->cacheService->set($this->key($customerId), $cart, self::CACHE_TTL);
    }

    public function removeItem(int $customerId, int $productId, int $skuId): void
    {
        $this->updateItemQuantity($customerId, $productId, $skuId, 0);
    }

    public function clearCart(int $customerId): void
    {
        $this->cacheService->delete($this->key($customerId));
    }

    public function clearShopCart(int $customerId, int $shopId): void
    {
        $cart = $this->getCart($customerId);
        unset($cart["shop_{$shopId}"]);
        $this->cacheService->set($this->key($customerId), $cart, self::CACHE_TTL);
    }

    private function key(int $customerId): string
    {
        return "cart:{$customerId}";
    }
}
