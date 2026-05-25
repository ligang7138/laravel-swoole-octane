<?php

use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\OrderController;
use Illuminate\Support\Facades\Route;

// 平台端 - 管理路由
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {

    // 商户管理
    Route::apiResource('merchants', MerchantController::class)->only(['index', 'store', 'show']);
    Route::post('merchants/{id}/activate', [MerchantController::class, 'activate']);
    Route::post('merchants/{id}/suspend', [MerchantController::class, 'suspend']);

    // 订单管理
    Route::apiResource('orders', OrderController::class)->only(['show']);
});
