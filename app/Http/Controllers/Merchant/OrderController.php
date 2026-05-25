<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\CreateProductRequest;
use App\Http\Requests\Merchant\UpdateProductRequest;
use App\Http\Resources\Order\OrderResource;
use App\Http\Resources\Product\ProductResource;
use App\Services\Order\OrderService;
use App\Services\Product\ProductService;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index()
    {
        $merchantId = $this->getMerchantId();
        $orders = $this->orderService->getMerchantOrders($merchantId);
        return OrderResource::collection($orders);
    }

    public function show(int $id)
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            return response()->json(['message' => '订单不存在'], 404);
        }
        return new OrderResource($order);
    }

    private function getMerchantId(): int
    {
        return auth()->user()->merchant?->id ?? 0;
    }
}
