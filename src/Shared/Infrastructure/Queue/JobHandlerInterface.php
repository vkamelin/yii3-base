<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

interface JobHandlerInterface
{
    public function handle(JobInterface $job): void;
}
