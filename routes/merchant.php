<?php

use App\Http\Controllers\Merchant\OrderController;
use App\Http\Controllers\Merchant\ProductController;
use Illuminate\Support\Facades\Route;

// B端 - 商户路由
Route::prefix('merchant')->middleware(['auth:sanctum'])->group(function () {

    // 订单
    Route::apiResource('orders', OrderController::class)->only(['index', 'show']);

    // 商品
    Route::apiResource('products', ProductController::class)->only(['index', 'store', 'show', 'update']);
    Route::put('products/{id}/status', [ProductController::class, 'updateStatus']);
});
