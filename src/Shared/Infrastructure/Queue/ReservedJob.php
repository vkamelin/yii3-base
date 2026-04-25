<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

final readonly class ReservedJob
{
    public function __construct(
        public QueueJobId $id,
        public string $type,
        public JobInterface $job,
        public int $attempt,
        public int $maxAttempts,
    ) {}
}

