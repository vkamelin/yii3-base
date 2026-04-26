<?php

declare(strict_types=1);

namespace App\Shared\Application\Tracing;

interface TraceContextInterface
{
    public function requestId(): ?string;

    public function correlationId(): ?string;

    public function userId(): ?string;

    public function source(): string;
}
