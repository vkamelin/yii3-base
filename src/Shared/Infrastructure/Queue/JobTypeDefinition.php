<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

final readonly class JobTypeDefinition
{
    /**
     * @param class-string<JobInterface> $jobClass
     * @param class-string<JobHandlerInterface> $handlerClass
     */
    public function __construct(
        public string $type,
        public string $jobClass,
        public string $handlerClass,
    ) {}
}

