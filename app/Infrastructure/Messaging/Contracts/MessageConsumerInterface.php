<?php

namespace App\Infrastructure\Messaging\Contracts;

interface MessageConsumerInterface
{
    public function subscribe(string $topic, string $group, callable $handler): void;

    public function start(): void;

    public function stop(): void;
}
