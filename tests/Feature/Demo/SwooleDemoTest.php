<?php

namespace Tests\Feature\Demo;

use Tests\TestCase;

/**
 * Swoole 协程 Demo 单元测试
 *
 * 测试环境要求：PHP 已安装 Swoole 扩展，且运行在 Octane/Swoole 模式下。
 * 如果在 PHP-FPM 下运行，部分测试可能跳过或失败。
 */
class SwooleDemoTest extends TestCase
{
    /**
     * 测试并发 HTTP 请求返回数组结果
     */
    public function test_concurrent_http_returns_array(): void
    {
        $response = $this->getJson('/demo/swoole/concurrent-http');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'case',
                'message',
                'time',
                'results' => [
                    '*' => ['site', 'status', 'code', 'length'],
                ],
            ])
            ->assertJson(['case' => 'concurrent_http']);

        $data = $response->json('results');
        $this->assertCount(3, $data);
        $this->assertContains('baidu', array_column($data, 'site'));
        $this->assertContains('swoole', array_column($data, 'site'));
        $this->assertContains('qq', array_column($data, 'site'));
    }

    /**
     * 测试 Worker Pool 完成所有任务
     */
    public function test_worker_pool_completes_all_tasks(): void
    {
        $response = $this->getJson('/demo/swoole/worker-pool');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'case',
                'workers',
                'tasks',
                'completed',
                'results' => [
                    '*' => ['task_id', 'worker_id', 'status'],
                ],
            ])
            ->assertJson([
                'case'      => 'worker_pool',
                'workers'   => 3,
                'tasks'     => 10,
                'completed' => 10,
            ]);

        // 验证总耗时小于串行时间（串行约 3s，并发应 < 2s）
        $time = (float) str_replace('s', '', $response->json('time'));
        $this->assertLessThan(3.0, $time, 'Worker Pool 应该并发执行，耗时小于串行时间');
    }

    /**
     * 测试 Channel push/pop 数据一致性
     */
    public function test_channel_push_pop(): void
    {
        $response = $this->getJson('/demo/swoole/channel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'case',
                'messages',
                'stats_before',
                'stats_after',
            ])
            ->assertJson([
                'case'     => 'channel',
                'messages' => ['message_1', 'message_2'],
            ]);

        // 验证 pop 后 length 减少
        $this->assertEquals(3, $response->json('stats_before.length'));
        $this->assertEquals(1, $response->json('stats_after.length'));
    }

    /**
     * 测试 defer 在协程结束时执行
     */
    public function test_defer_executes_on_coroutine_exit(): void
    {
        $response = $this->getJson('/demo/swoole/defer');

        $response->assertStatus(200)
            ->assertJsonStructure(['case', 'logs'])
            ->assertJson(['case' => 'defer']);

        $logs = $response->json('logs');
        $this->assertContains('协程开始', $logs);
        $this->assertContains('defer 执行：协程结束，清理资源', $logs);
        $this->assertContains('协程执行完毕', $logs);

        // 验证执行顺序：开始 -> 执行完毕 -> defer
        $startIndex = array_search('协程开始', $logs);
        $doneIndex = array_search('协程执行完毕', $logs);
        $deferIndex = array_search('defer 执行：协程结束，清理资源', $logs);
        $this->assertLessThan($deferIndex, $doneIndex, 'defer 应在协程执行完毕后执行');
    }

    /**
     * 测试 Redis 连接池并发安全
     */
    public function test_redis_pool_get_put(): void
    {
        $response = $this->getJson('/demo/swoole/redis-pool');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'case',
                'results' => ['key1', 'key2'],
                'errors',
            ])
            ->assertJson([
                'case'           => 'redis_pool',
                'coroutine_safe' => true,
            ]);

        // 验证两个协程的 Redis 操作都成功，没有串读
        $this->assertEquals('value_from_coroutine_1', $response->json('results.key1'));
        $this->assertEquals('value_from_coroutine_2', $response->json('results.key2'));
        $this->assertEmpty($response->json('errors'));
    }

    /**
     * 测试协程 sleep 不阻塞 Worker
     */
    public function test_sleep_does_not_block(): void
    {
        $response = $this->getJson('/demo/swoole/sleep');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'case',
                'time',
                'logs',
            ])
            ->assertJson(['case' => 'sleep']);

        // 两个协程 sleep 0.5s + 0.3s，并发总耗时应约 0.5s，不是 0.8s
        $time = (float) str_replace('s', '', $response->json('time'));
        $this->assertLessThan(0.8, $time, '协程 sleep 应该并发执行，总耗时接近最长的一个');

        $logs = $response->json('logs');
        $this->assertContains('协程1 结束', $logs);
        $this->assertContains('协程2 结束', $logs);
    }

    /**
     * 测试 Runtime Hook 检测
     */
    public function test_check_hook(): void
    {
        $response = $this->getJson('/demo/swoole/check-hook');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'case',
                'hook_flags',
                'hook_tcp',
                'hook_stdio',
                'hook_sleep',
                'coroutine_enabled',
            ])
            ->assertJson(['case' => 'check_hook']);

        // 如果 Swoole 已加载，验证关键 Hook 已启用
        if (extension_loaded('swoole')) {
            $this->assertTrue($response->json('hook_tcp'));
            $this->assertTrue($response->json('coroutine_enabled'));
        }
    }
}
