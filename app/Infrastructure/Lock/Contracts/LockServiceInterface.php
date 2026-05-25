<?php

namespace App\Infrastructure\Lock\Contracts;

interface LockServiceInterface
{
    public function acquire(string $key, int $ttl = 10): bool;

    public function release(string $key): bool;

    public function runWithLock(string $key, int $ttl, callable $callback): mixed;
}
