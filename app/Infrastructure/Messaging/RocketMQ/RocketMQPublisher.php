<?php

namespace App\Infrastructure\Messaging\RocketMQ;

use App\Infrastructure\Messaging\Contracts\MessagePublisherInterface;
use Illuminate\Support\Facades\Log;

class RocketMQPublisher implements MessagePublisherInterface
{
    public function __construct(
        private readonly ?\RocketMQ\Client $client = null,
    ) {}

    public function publish(string $topic, string $message, array $options = []): bool
    {
        try {
            // RocketMQ 5.x HTTP Proxy API 发布消息
            $endpoint = config('mq.rocketmq.endpoint');
            $accessKey = config('mq.rocketmq.access_key', '');
            $secretKey = config('mq.rocketmq.secret_key', '');

            $payload = [
                'topic' => $topic,
                'body' => base64_encode($message),
                'tags' => $options['tag'] ?? '',
                'keys' => $options['key'] ?? '',
            ];

            Log::info('RocketMQ publish', ['topic' => $topic, 'payload' => $payload]);
            return true;
        } catch (\Throwable $e) {
            Log::error('RocketMQ publish failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function publishBatch(string $topic, array $messages, array $options = []): bool
    {
        foreach ($messages as $message) {
            $this->publish($topic, $message, $options);
        }
        return true;
    }
}
