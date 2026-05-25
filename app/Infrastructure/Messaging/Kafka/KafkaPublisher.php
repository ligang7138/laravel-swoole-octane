<?php

namespace App\Infrastructure\Messaging\Kafka;

use App\Infrastructure\Messaging\Contracts\MessagePublisherInterface;
use Illuminate\Support\Facades\Log;

class KafkaPublisher implements MessagePublisherInterface
{
    public function publish(string $topic, string $message, array $options = []): bool
    {
        try {
            $brokers = config('mq.kafka.brokers');

            $payload = [
                'topic' => $topic,
                'body' => $message,
                'key' => $options['key'] ?? null,
                'headers' => $options['headers'] ?? [],
            ];

            Log::info('Kafka publish', ['topic' => $topic, 'payload' => $payload]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Kafka publish failed', [
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
