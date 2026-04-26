<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

final readonly class WorkerOptions
{
    public function __construct(
        public int $sleepSeconds = 3,
        public int $maxJobs = 0,
        public int $maxTimeSeconds = 0,
        public int $memoryLimitMb = 128,
        public string $queueName = 'mysql',
    ) {}
}
