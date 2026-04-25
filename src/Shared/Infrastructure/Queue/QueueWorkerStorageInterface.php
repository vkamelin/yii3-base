<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

interface QueueWorkerStorageInterface extends QueueInterface
{
    public function reserveNext(): ?ReservedJob;

    public function markDone(ReservedJob $job): void;

    public function release(ReservedJob $job, int $delaySeconds, string $lastError): void;

    public function markFailed(ReservedJob $job, string $lastError): void;
}

