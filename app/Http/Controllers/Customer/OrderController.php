<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateOrderRequest;
use App\Http\Resources\Order\OrderResource;
use App\Infrastructure\Cache\Redis\RedisCacheService;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use Swoole\Coroutine\System;
use Swoole\Coroutine\Channel;

class OrderController extends Controller
{
    public static $count = 0;
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CartService $cartService,
        private readonly RedisCacheService $cacheService,
    ) {}

    public function index()
    {
        $start = microtime(true);
        $results = [];
        $timeout = 3.0;

        try {


            go(function () {
                for ($i = 0; $i < 10; $i++) {
                    echo 'go count ' . $i . '-'.now()->toDateTimeString() . PHP_EOL;
                    // System::sleep(0.01);
                    usleep(5000);
                }
            });

            go(function () {
                for ($i = 0; $i < 10; $i++) {
                    echo 'php count ' . $i . '-'.now()->toDateTimeString() .  PHP_EOL;
                    // System::sleep(0.02);
                    usleep(5000);
                }
            });

            $responses = \Illuminate\Support\Facades\Http::timeout($timeout)
                ->pool(fn ($pool) => [
                    $pool->get('https://www.baidu.com'),
                    $pool->get('https://www.swoole.com'),
                    $pool->get('https://www.qq.com'),
                ]);

            $sites = ['baidu', 'swoole', 'qq'];
            foreach ($responses as $index => $response) {
                $results[] = [
                    'site' => $sites[$index],
                    'length' => $response->successful() ? strlen($response->body()) : 0,
                    'status' => $response->successful() ? 'success' : 'failed',
                    'code' => $response->status()
                ];
            }
        } catch (\Throwable $e) {
            $results = ['error' => $e->getMessage()];
        }

        $elapsed = microtime(true) - $start;

        // 使用 Laravel 的 terminating 回调在请求结束后重置计数器
        // app()->terminating(function () {
        //     echo 'defer reset count' . PHP_EOL;
        //     self::$count = 0;
        // });

        defer(function () {
            echo 'defer reset count 3333' . PHP_EOL;
            self::$count = 0;
        });

        return response()->json([
            'count' => self::$count++,
            'message' => '并发请求完成',
            'time' => round($elapsed, 3) . 's',
            'timeout' => $timeout . 's',
            'results' => $results,
        ]);
    }

    public function store(CreateOrderRequest $request)
    {
        $customerId = $this->getCustomerId();
        $order = $this->orderService->createOrder(
            $customerId,
            $request->merchant_id,
            $request->shop_id,
            $request->address_id,
            $request->items,
            $request->remark ?? '',
        );

        $this->cartService->clearShopCart($customerId, $request->shop_id);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function show(int $id)
    {
        $order = $this->orderService->getOrder($id);
        if (!$order) {
            return response()->json(['message' => '订单不存在'], 404);
        }
        return new OrderResource($order);
    }

    public function cancel(int $id)
    {
        $customerId = $this->getCustomerId();
        $order = $this->orderService->cancelOrder($id, $customerId);
        return new OrderResource($order);
    }

    /**
     * Worker Pool 演示：并发处理任务
     * 模拟从 MySQL 查 100 条数据，交给多个协程并发处理
     */
    public function workerPoolDemo()
    {
        $start = microtime(true);
        $workerNum = 3;  // 3 个 Worker
        $taskNum = 10;   // 10 个任务

        // 模拟从 MySQL 查出的数据
        $tasks = [];
        for ($i = 1; $i <= $taskNum; $i++) {
            $tasks[] = [
                'id' => $i,
                'data' => "订单-$i",
                'duration' => rand(1, 5) / 10, // 模拟处理耗时
            ];
        }

        // 使用 Octane 内部的协程调度方式：Coroutine::create + WaitGroup
        $results = [];
        $wg = new \Swoole\Coroutine\WaitGroup();

        foreach ($tasks as $task) {
            $wg->add();
            \Swoole\Coroutine::create(function () use ($task, $workerNum, $wg, &$results) {
                try {
                    // 模拟耗时处理（实际场景：调用外部API、写数据库等）
                    \Swoole\Coroutine\System::sleep($task['duration']);

                    $results[] = [
                        'task_id' => $task['id'],
                        'worker_id' => $task['id'] % $workerNum,
                        'data' => $task['data'],
                        'duration' => $task['duration'] . 's',
                        'status' => 'success',
                    ];
                } finally {
                    $wg->done();
                }
            });
        }

        // 等待所有协程完成，最多等 10 秒
        $wg->wait(10);

        $elapsed = microtime(true) - $start;

        return response()->json([
            'message' => 'Worker Pool 演示完成',
            'workers' => $workerNum,
            'tasks' => $taskNum,
            'completed' => count($results),
            'time' => round($elapsed, 3) . 's',
            'results' => $results,
        ]);
    }

    private function getCustomerId(): int
    {
        return auth()->user()->customer?->id ?? 0;
    }

    /**
     * 验证 Swoole Runtime Hook 是否生效
     */
    public function checkHook()
    {
        $hookFlags = \Swoole\Runtime::getHookFlags();
        $this->cacheService->set('key1', 'value-123');
        $this->cacheService->set('key2', 'value-421');
        // 每个协程使用独立的原生 Redis 连接，避免连接污染
        $results = [];
        $errors = [];
        $wg = new \Swoole\Coroutine\WaitGroup();
        $wg->add(2);

        go(function () use ($wg, &$results, &$errors) {
            try {
                
                $results['key1'] = $this->cacheService->get('key1');
            } catch (\Throwable $e) {
                $errors['key1'] = $e->getMessage();
            } finally {
                $wg->done();
            }
        });

        go(function () use ($wg, &$results, &$errors) {
            try {
                
                $results['key2'] = $this->cacheService->get('key2');
            } catch (\Throwable $e) {
                $errors['key2'] = $e->getMessage();
            } finally {
                $wg->done();
            }
        });

        $wg->wait();

        return response()->json([
            'hook_flags' => $hookFlags,
            'hook_tcp' => (bool) ($hookFlags & SWOOLE_HOOK_TCP),
            'hook_stdio' => (bool) ($hookFlags & SWOOLE_HOOK_STDIO),
            'hook_sleep' => (bool) ($hookFlags & SWOOLE_HOOK_SLEEP),
            'coroutine_enabled' => extension_loaded('swoole'),
            'redis_results' => $results,
            'errors' => $errors,
        ]);
    }
}
