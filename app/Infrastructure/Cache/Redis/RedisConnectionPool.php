<?php

namespace App\Infrastructure\Cache\Redis;

use Swoole\Coroutine\Channel;

/**
 * Redis 连接池（协程安全）
 *
 * 限制最大连接数，复用连接，避免并发高时连接暴涨
 */
class RedisConnectionPool
{
    private static ?Channel $pool = null;
    private static int $maxSize = 64;
    private static array $config = [];

    /**
     * 初始化连接池
     */
    public static function init(array $config, int $maxSize = 10): void
    {
        self::$config = $config;
        self::$maxSize = $maxSize;
        self::$pool = new Channel($maxSize);

        // 预创建连接
        for ($i = 0; $i < $maxSize; $i++) {
            $redis = self::createConnection();
            if ($redis) {
                self::$pool->push($redis);
            }
        }
    }

    /**
     * 获取连接（从池中取，池空则阻塞等待）
     */
    public static function get(float $timeout = 3.0): ?\Redis
    {
        if (self::$pool === null) {
            self::init([
                'host' => config('database.redis.cache.host', '127.0.0.1'),
                'port' => (int) config('database.redis.cache.port', 6379),
                'password' => config('database.redis.cache.password'),
                'database' => (int) config('database.redis.cache.database', 1),
            ]);
        }

        $redis = self::$pool->pop($timeout);

        // 检查连接是否有效，无效则重新创建
        if ($redis !== false && self::isConnected($redis)) {
            return $redis;
        }

        return self::createConnection();
    }

    /**
     * 归还连接到池中
     */
    public static function put(\Redis $redis): void
    {
        if (self::$pool === null || !self::isConnected($redis)) {
            return;
        }

        // 池满则丢弃
        if (self::$pool->length() >= self::$maxSize) {
            $redis->close();
            return;
        }

        self::$pool->push($redis);
    }

    /**
     * 创建新连接
     */
    private static function createConnection(): ?\Redis
    {
        if (empty(self::$config)) {
            return null;
        }

        $redis = new \Redis();
        $connected = $redis->connect(self::$config['host'], self::$config['port'], 1);

        if (!$connected) {
            return null;
        }

        if (!empty(self::$config['password'])) {
            $redis->auth(self::$config['password']);
        }

        $redis->select(self::$config['database']);

        return $redis;
    }

    /**
     * 检查连接是否有效
     */
    private static function isConnected(\Redis $redis): bool
    {
        try {
            return $redis->ping() === true || $redis->ping() === '+PONG';
        } catch (\Throwable) {
            return false;
        }
    }
}
