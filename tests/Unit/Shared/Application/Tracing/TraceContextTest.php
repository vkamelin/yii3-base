<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Tracing;

use App\Shared\Application\Tracing\TraceContext;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertSame;

final class TraceContextTest extends Unit
{
    public function testCreatesTraceContext(): void
    {
        $context = new TraceContext(
            requestId: 'req-12345678',
            correlationId: 'corr-12345678',
            userId: 'user-1',
            source: TraceContext::SOURCE_API,
        );

        assertSame('req-12345678', $context->requestId());
        assertSame('corr-12345678', $context->correlationId());
        assertSame('user-1', $context->userId());
        assertSame(TraceContext::SOURCE_API, $context->source());
    }
}
