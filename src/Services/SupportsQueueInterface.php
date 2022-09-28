<?php

namespace Kuptsov\RabbitmqService\Services;

interface SupportsQueueInterface
{
    public function getSupportedQueues(): array;
}
