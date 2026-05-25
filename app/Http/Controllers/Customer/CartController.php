<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(): JsonResponse
    {
        $customerId = auth()->user()->customer?->id ?? 0;
        $cart = $this->cartService->getCart($customerId);
        return response()->json(['data' => $cart]);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|integer',
            'shop_id' => 'required|integer',
            'product_id' => 'required|integer',
            'sku_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
        ]);

        $customerId = auth()->user()->customer?->id ?? 0;
        $this->cartService->addItem(
            $customerId,
            $request->merchant_id,
            $request->shop_id,
            $request->product_id,
            $request->sku_id,
            $request->quantity,
            $request->price,
        );

        return response()->json(['message' => '已添加到购物车']);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'sku_id' => 'required|integer',
            'quantity' => 'required|integer|min:0',
        ]);

        $customerId = auth()->user()->customer?->id ?? 0;
        $this->cartService->updateItemQuantity(
            $customerId,
            $request->product_id,
            $request->sku_id,
            $request->quantity,
        );

        return response()->json(['message' => '购物车已更新']);
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'sku_id' => 'required|integer',
        ]);

        $customerId = auth()->user()->customer?->id ?? 0;
        $this->cartService->removeItem($customerId, $request->product_id, $request->sku_id);
        return response()->json(['message' => '已从购物车移除']);
    }

    public function clear(): JsonResponse
    {
        $customerId = auth()->user()->customer?->id ?? 0;
        $this->cartService->clearCart($customerId);
        return response()->json(['message' => '购物车已清空']);
    }
}
