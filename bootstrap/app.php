<?php
/*
 * @Author: ligang ligang.bj@acewill.cn
 * @Date: 2026-03-15 00:54:00
 * @LastEditors: ligang ligang.bj@acewill.cn
 * @LastEditTime: 2026-05-29 03:53:51
 * @FilePath: /sxw/bootstrap/app.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')->group(base_path('routes/customer.php'));
            Route::middleware('api')->group(base_path('routes/merchant.php'));
            Route::middleware('api')->group(base_path('routes/admin.php'));
            Route::middleware('api')->group(base_path('routes/demo.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtAuth::class,
            'rbac' => \App\Http\Middleware\RbacPermission::class,
            'request.log' => \App\Http\Middleware\RequestLog::class,
            'rate.limit' => \App\Http\Middleware\RateLimit::class,
            'cross.domain' => \App\Http\Middleware\CrossDomain::class,
        ]);

        $middleware->statefulApi();

        // API 路由认证失败时返回 401 JSON 响应
        $middleware->redirectGuestsTo(function (Request $request) {
            // 纯 API 项目，始终返回 null 让中间件返回 401 JSON
            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 处理未认证异常 - API 请求返回 JSON 而不是重定向
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'code' => 401,
                    'msg' => '请先登录',
                    'data' => null,
                ], 401);
            }
        });
    })->create();
