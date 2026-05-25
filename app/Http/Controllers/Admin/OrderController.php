<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Services\Order\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function show(int $id)
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            return response()->json(['message' => '订单不存在'], 404);
        }
        return new OrderResource($order);
    }
}
