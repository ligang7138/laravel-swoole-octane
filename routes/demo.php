<?php

use App\Http\Controllers\Demo\Swoole\SwooleDemoController;
use Illuminate\Support\Facades\Route;

/**
 * Demo 路由 - Swoole 协程案例
 *
 * 仅供开发和调试使用，生产环境建议禁用或加 IP 白名单。
 */
Route::prefix('demo')->group(function () {

    // Swoole 协程案例
    Route::prefix('swoole')->group(function () {
        Route::get('concurrent-http', [SwooleDemoController::class, 'concurrentHttp']);
        Route::get('worker-pool', [SwooleDemoController::class, 'workerPool']);
        Route::get('channel', [SwooleDemoController::class, 'channel']);
        Route::get('defer', [SwooleDemoController::class, 'defer']);
        Route::get('redis-pool', [SwooleDemoController::class, 'redisPool']);
        Route::get('sleep', [SwooleDemoController::class, 'sleep']);
        Route::get('check-hook', [SwooleDemoController::class, 'checkHook']);
    });
});
