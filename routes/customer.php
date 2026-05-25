<?php

use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\ProductController;
use Illuminate\Support\Facades\Route;

// C端 - 消费者路由
Route::prefix('customer')->group(function () {

    // 订单
    Route::get('orders/check-hook', [OrderController::class, 'checkHook']);
    Route::get('orders/worker-pool', [OrderController::class, 'workerPoolDemo']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);

    // 支付
    Route::post('payments', [PaymentController::class, 'store']);
    Route::post('payments/callback/{channel}', [PaymentController::class, 'callback'])->withoutMiddleware(['auth:sanctum']);

    // 商品
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('shops/{shopId}/products', [ProductController::class, 'shopProducts']);

    // 购物车
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/add', [CartController::class, 'add']);
    Route::put('cart/update', [CartController::class, 'update']);
    Route::delete('cart/remove', [CartController::class, 'remove']);
    Route::delete('cart/clear', [CartController::class, 'clear']);
});
