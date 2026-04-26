<?php

declare(strict_types=1);

namespace App\Shared\Application\Tracing;

final readonly class TraceContext implements TraceContextInterface
{
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_CONSOLE = 'console';
    public const SOURCE_QUEUE = 'queue';
    public const SOURCE_SYSTEM = 'system';

    public function __construct(
        private ?string $requestId,
        private ?string $correlationId,
        private ?string $userId,
        private string $source,
    ) {}

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function source(): string
    {
        return $this->source;
    }
}
