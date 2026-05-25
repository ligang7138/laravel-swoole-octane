<?php

namespace App\Infrastructure\Cache\Contracts;

interface CacheServiceInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    public function delete(string $key): bool;

    public function has(string $key): bool;

    public function remember(string $key, int $ttl, callable $callback): mixed;
}
