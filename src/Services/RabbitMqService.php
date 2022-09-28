<?php

namespace Kuptsov\RabbitmqService\Services;

use Illuminate\Support\Facades\Log;

class RabbitMqService
{
    /** @var */
    private $queues;

    /** @var RabbitMQQueue|null */
    private $connection;

    public function __construct()
    {
        $this->connection = app('queue')->connection('rabbitmq');
        $this->initSupportedQueues();
    }

    private function initSupportedQueues(): void
    {
        $supportedClass = (string)config('rabbitmq-service.supported_queues');
        $reflectionClass = new \ReflectionClass($supportedClass);
        $supportedQueuesInstance = $reflectionClass->newInstance();

        if (!$supportedQueuesInstance instanceof SupportsQueueInterface) {
            throw new \DomainException('Invalid supported_class');
        }

        $this->queues = array_fill_keys($supportedQueuesInstance->getSupportedQueues(), true);
    }

    public function pop(string $queue)
    {
        try {
            return $this->connection->pop($queue);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return null;
        }
    }

    public function push(array $message, string $queue): void
    {
        try {
            $this->connection->pushRaw(json_encode($message), $queue);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    public function isSupportedQueue(string $queue): bool
    {
        return array_key_exists($queue, $this->queues);
    }

    public function getSupportedQueues(): array
    {
        $supportedClass = (string)config('rabbitmq-service.supported_queues');
        $reflectionClass = new \ReflectionClass($supportedClass);
        $supportedQueuesInstance = $reflectionClass->newInstance();

        if (!$supportedQueuesInstance instanceof SupportsQueueInterface) {
            throw new \DomainException('Invalid supported_class');
        }

        return $supportedQueuesInstance->getSupportedQueues();
    }

    public function clearQueue(string $queue): void
    {
        if (!$this->isSupportedQueue($queue)) {
            return;
        }
        if ($this->connection->isQueueExists($queue)) {
            $this->connection->purge($queue);
        }
    }

    public function size(string $queue): int
    {
        if (!$this->isSupportedQueue($queue)) {
            return 0;
        }
        if ($this->connection->isQueueExists($queue)) {
            return $this->connection->size($queue);
        }
        return 0;
    }
}
