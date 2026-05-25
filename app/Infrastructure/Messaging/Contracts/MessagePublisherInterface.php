<?php

namespace App\Infrastructure\Messaging\Contracts;

interface MessagePublisherInterface
{
    public function publish(string $topic, string $message, array $options = []): bool;

    public function publishBatch(string $topic, array $messages, array $options = []): bool;
}
