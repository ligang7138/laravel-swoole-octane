<?php

namespace App\Infrastructure\Lock\Redis;

use App\Infrastructure\Lock\Contracts\LockServiceInterface;
use Illuminate\Support\Facades\Redis;

class RedisLockService implements LockServiceInterface
{
    public function acquire(string $key, int $ttl = 10): bool
    {
        $lockKey = $this->prefix($key);
        $token = uniqid('', true);

        $acquired = Redis::connection('cache')->set($lockKey, $token, 'NX', 'EX', $ttl);
        return $acquired === true || $acquired === 'OK';
    }

    public function release(string $key): bool
    {
        $lockKey = $this->prefix($key);

        $script = <<<'LUA'
if redis.call("get", KEYS[1]) then
    return redis.call("del", KEYS[1])
end
return 0
LUA;

        return Redis::connection('cache')->eval($script, 1, $lockKey) > 0;
    }

    public function runWithLock(string $key, int $ttl, callable $callback): mixed
    {
        if (!$this->acquire($key, $ttl)) {
            throw new \RuntimeException("获取锁失败: {$key}");
        }

        try {
            return $callback();
        } finally {
            $this->release($key);
        }
    }

    private function prefix(string $key): string
    {
        return config('cache.prefix', 'sxw') . ':lock:' . $key;
    }
}
