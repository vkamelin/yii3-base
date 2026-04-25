<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

interface QueueInterface
{
    /**
     * Pushes a job for immediate or delayed execution.
     *
     * @param int $delaySeconds Delay before availability. Must be >= 0.
     * @param int $maxAttempts Maximum number of handling attempts. Must be >= 1.
     *
     * @return string UUID v7 job identifier.
     */
    public function push(JobInterface $job, int $delaySeconds = 0, int $maxAttempts = 3): string;

    /**
     * Pushes a job with explicit delay.
     *
     * @param int $delaySeconds Delay before availability. Must be >= 0.
     * @param int $maxAttempts Maximum number of handling attempts. Must be >= 1.
     *
     * @return string UUID v7 job identifier.
     */
    public function pushDelayed(JobInterface $job, int $delaySeconds, int $maxAttempts = 3): string;
}
