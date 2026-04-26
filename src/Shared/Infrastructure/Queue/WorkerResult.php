<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

final readonly class WorkerResult
{
    public function __construct(
        public int $processedJobs,
        public int $failedJobs,
        public string $stopReason,
    ) {}
}
