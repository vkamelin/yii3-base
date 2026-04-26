<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Tracing;

use App\Shared\Application\Tracing\TraceId;
use Ramsey\Uuid\Uuid;

final class TraceIdGenerator
{
    public function generate(): TraceId
    {
        return TraceId::fromString(Uuid::uuid7()->toString());
    }
}
