<?php

namespace App\Http\Controllers\Demo\Swoole;

use App\Http\Controllers\Controller;
use App\Infrastructure\Cache\Redis\RedisConnectionPool;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\System;
use Swoole\Coroutine\WaitGroup;

/**
 * Swoole 协程 Demo
 *
 * 本 Controller 汇总项目开发过程中验证过的 Swoole 协程案例，
 * 供团队成员参考和复用。所有案例均已在 Octane + Swoole 环境下验证通过。
 */
class SwooleDemoController extends Controller
{
    /**
     * 案例 1：并发 HTTP 请求
     *
     * 使用 go() + Channel 并发请求多个外部接口，带超时控制。
     * 适用场景：需要并行调用多个第三方 API 并聚合结果。
     */
    public function concurrentHttp()
    {
        $start = microtime(true);
        $timeout = 3.0;

        $urls = [
            'baidu'   => ['host' => 'www.baidu.com', 'port' => 443, 'ssl' => true, 'path' => '/'],
            'swoole'  => ['host' => 'www.swoole.com', 'port' => 443, 'ssl' => true, 'path' => '/'],
            'qq'      => ['host' => 'www.qq.com', 'port' => 443, 'ssl' => true, 'path' => '/'],
        ];

        $channel = new Channel(count($urls));

        foreach ($urls as $name => $config) {
            go(function () use ($name, $config, $channel, $timeout) {
                try {
                    $client = new \Swoole\Coroutine\Http\Client($config['host'], $config['port'], $config['ssl']);
                    $client->set(['timeout' => $timeout]);
                    $client->get($config['path']);

                    $channel->push([
                        'site'   => $name,
                        'status' => $client->statusCode === 200 ? 'success' : 'failed',
                        'code'   => $client->statusCode,
                        'length' => strlen($client->body),
                    ]);
                    $client->close();
                } catch (\Throwable $e) {
                    $channel->push([
                        'site'   => $name,
                        'status' => 'error',
                        'code'   => 0,
                        'length' => 0,
                        'error'  => $e->getMessage(),
                    ]);
                }
            });
        }

        $results = [];
        for ($i = 0; $i < count($urls); $i++) {
            $results[] = $channel->pop($timeout + 1);
        }
        $channel->close();

        $elapsed = microtime(true) - $start;

        return response()->json([
            'case'    => 'concurrent_http',
            'message' => '并发 HTTP 请求完成',
            'time'    => round($elapsed, 3) . 's',
            'timeout' => $timeout . 's',
            'results' => $results,
        ]);
    }

    /**
     * 案例 2：Worker Pool 模式
     *
     * 固定 Worker 数量，通过 Channel 分发任务，WaitGroup 等待完成。
     * 适用场景：批量数据处理，控制并发度，避免资源打满。
     */
    public function workerPool()
    {
        $start = microtime(true);
        $workerNum = 3;
        $taskNum = 10;

        // 模拟从 MySQL 查出的数据
        $tasks = [];
        for ($i = 1; $i <= $taskNum; $i++) {
            $tasks[] = [
                'id'       => $i,
                'data'     => "订单-{$i}",
                'duration' => rand(1, 5) / 10,
            ];
        }

        $taskChan = new Channel($taskNum);
        $resultChan = new Channel($taskNum);

        // 填充任务队列
        foreach ($tasks as $task) {
            $taskChan->push($task);
        }
        $taskChan->close();

        $wg = new WaitGroup();

        // 启动 Worker
        for ($w = 0; $w < $workerNum; $w++) {
            $wg->add();
            go(function () use ($w, $taskChan, $resultChan, $wg) {
                while (true) {
                    $task = $taskChan->pop(0.5);
                    if ($task === false) {
                        break;
                    }

                    // 模拟耗时处理
                    System::sleep($task['duration']);

                    $resultChan->push([
                        'task_id'   => $task['id'],
                        'worker_id' => $w,
                        'data'      => $task['data'],
                        'duration'  => $task['duration'] . 's',
                        'status'    => 'success',
                    ]);
                }
                $wg->done();
            });
        }

        // 等待所有 Worker 完成
        go(function () use ($wg, $resultChan) {
            $wg->wait();
            $resultChan->close();
        });

        // 收集结果
        $results = [];
        while (true) {
            $res = $resultChan->pop(0.5);
            if ($res === false) {
                break;
            }
            $results[] = $res;
        }

        $elapsed = microtime(true) - $start;

        return response()->json([
            'case'      => 'worker_pool',
            'message'   => 'Worker Pool 演示完成',
            'workers'   => $workerNum,
            'tasks'     => $taskNum,
            'completed' => count($results),
            'time'      => round($elapsed, 3) . 's',
            'results'   => $results,
        ]);
    }

    /**
     * 案例 3：Channel 基础 API
     *
     * 演示 Channel 的 push/pop/close/length 等核心操作。
     * 适用场景：协程间通信、数据传递、任务队列。
     */
    public function channel()
    {
        $chan = new Channel(3);

        // push
        $chan->push('message_1');
        $chan->push('message_2');
        $chan->push('message_3');

        $stats = [
            'capacity'  => $chan->capacity,
            'length'    => $chan->length(),
            'is_empty'  => $chan->isEmpty(),
            'is_full'   => $chan->isFull(),
        ];

        // pop
        $messages = [];
        $messages[] = $chan->pop(1.0);
        $messages[] = $chan->pop(1.0);

        $stats_after = [
            'capacity'  => $chan->capacity,
            'length'    => $chan->length(),
            'is_empty'  => $chan->isEmpty(),
            'is_full'   => $chan->isFull(),
        ];

        $chan->close();

        return response()->json([
            'case'         => 'channel',
            'messages'     => $messages,
            'stats_before' => $stats,
            'stats_after'  => $stats_after,
        ]);
    }

    /**
     * 案例 4：defer 机制
     *
     * 演示在协程结束时自动执行清理操作，类似 Go 的 defer。
     * 适用场景：资源释放、计数器重置、日志记录。
     */
    public function defer()
    {
        $logs = [];

        go(function () use (&$logs) {
            $logs[] = '协程开始';

            defer(function () use (&$logs) {
                $logs[] = 'defer 执行：协程结束，清理资源';
            });

            System::sleep(0.1);
            $logs[] = '协程执行完毕';
        });

        // 等待协程完成
        System::sleep(0.2);

        return response()->json([
            'case' => 'defer',
            'logs' => $logs,
        ]);
    }

    /**
     * 案例 5：Redis 连接池
     *
     * 演示协程安全的 Redis 连接池使用，get/put 模式。
     * 适用场景：高并发下 Redis 操作，避免连接污染。
     */
    public function redisPool()
    {
        $results = [];
        $errors = [];
        $wg = new WaitGroup();
        $wg->add(2);

        go(function () use ($wg, &$results, &$errors) {
            try {
                $redis = RedisConnectionPool::get();
                $redis->set('demo_key_1', 'value_from_coroutine_1');
                $results['key1'] = $redis->get('demo_key_1');
                RedisConnectionPool::put($redis);
            } catch (\Throwable $e) {
                $errors['key1'] = $e->getMessage();
            } finally {
                $wg->done();
            }
        });

        go(function () use ($wg, &$results, &$errors) {
            try {
                $redis = RedisConnectionPool::get();
                $redis->set('demo_key_2', 'value_from_coroutine_2');
                $results['key2'] = $redis->get('demo_key_2');
                RedisConnectionPool::put($redis);
            } catch (\Throwable $e) {
                $errors['key2'] = $e->getMessage();
            } finally {
                $wg->done();
            }
        });

        $wg->wait();

        return response()->json([
            'case'          => 'redis_pool',
            'results'       => $results,
            'errors'        => $errors,
            'pool_size'     => 64,
            'coroutine_safe'=> true,
        ]);
    }

    /**
     * 案例 6：协程级 sleep
     *
     * 演示 System::sleep() 只阻塞当前协程，不阻塞 Worker 进程。
     * 适用场景：模拟 IO 耗时、延时任务、限流。
     */
    public function sleep()
    {
        $start = microtime(true);
        $logs = [];
        $wg = new WaitGroup();
        $wg->add(2);

        go(function () use ($wg, &$logs) {
            $logs[] = '协程1 开始 sleep 0.5s';
            System::sleep(0.5);
            $logs[] = '协程1 结束';
            $wg->done();
        });

        go(function () use ($wg, &$logs) {
            $logs[] = '协程2 开始 sleep 0.3s';
            System::sleep(0.3);
            $logs[] = '协程2 结束';
            $wg->done();
        });

        $wg->wait();
        $elapsed = microtime(true) - $start;

        return response()->json([
            'case'         => 'sleep',
            'message'      => '两个协程并发 sleep，总耗时约 0.5s（不是 0.8s）',
            'time'         => round($elapsed, 3) . 's',
            'logs'         => $logs,
        ]);
    }

    /**
     * 案例 7：Swoole Runtime Hook 检测
     *
     * 检测当前环境是否启用了 Swoole Runtime Hook。
     * 适用场景：环境检查、调试排错。
     */
    public function checkHook()
    {
        $hookFlags = \Swoole\Runtime::getHookFlags();

        return response()->json([
            'case'              => 'check_hook',
            'hook_flags'        => $hookFlags,
            'hook_tcp'          => (bool) ($hookFlags & SWOOLE_HOOK_TCP),
            'hook_stdio'        => (bool) ($hookFlags & SWOOLE_HOOK_STDIO),
            'hook_sleep'        => (bool) ($hookFlags & SWOOLE_HOOK_SLEEP),
            'hook_file'         => (bool) ($hookFlags & SWOOLE_HOOK_FILE),
            'coroutine_enabled' => extension_loaded('swoole'),
        ]);
    }
}
