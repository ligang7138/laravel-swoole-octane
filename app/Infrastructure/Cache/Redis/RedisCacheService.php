<?php

namespace App\Infrastructure\Cache\Redis;

use App\Infrastructure\Cache\Contracts\CacheServiceInterface;

class RedisCacheService implements CacheServiceInterface
{
    /**
     * 从连接池获取 Redis 连接
     */
    private function getConnection(): \Redis
    {
        return RedisConnectionPool::get();
    }

    /**
     * 归还连接到连接池
     */
    private function releaseConnection(\Redis $redis): void
    {
        RedisConnectionPool::put($redis);
    }

    public function get(string $key): mixed
    {
        $redis = $this->getConnection();
        try {
            $value = $redis->get($this->prefix($key));
            return $value !== null ? json_decode($value, true) : null;
        } finally {
            $this->releaseConnection($redis);
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $redis = $this->getConnection();
        try {
            $serialized = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($ttl) {
                return $redis->setex($this->prefix($key), $ttl, $serialized) === true;
            }
            return $redis->set($this->prefix($key), $serialized) === true;
        } finally {
            $this->releaseConnection($redis);
        }
    }

    public function delete(string $key): bool
    {
        $redis = $this->getConnection();
        try {
            return $redis->del($this->prefix($key)) > 0;
        } finally {
            $this->releaseConnection($redis);
        }
    }

    public function has(string $key): bool
    {
        $redis = $this->getConnection();
        try {
            return (bool) $redis->exists($this->prefix($key));
        } finally {
            $this->releaseConnection($redis);
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    private function prefix(string $key): string
    {
        return config('cache.prefix', 'sxw') . ':' . $key;
    }
}
