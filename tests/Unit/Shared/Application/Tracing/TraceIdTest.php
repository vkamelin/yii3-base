<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Tracing;

use App\Shared\Application\Tracing\TraceId;
use Codeception\Test\Unit;
use InvalidArgumentException;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class TraceIdTest extends Unit
{
    public function testAcceptsValidTraceId(): void
    {
        $traceId = TraceId::fromString('req-abc12345');

        assertSame('req-abc12345', $traceId->toString());
        assertTrue(TraceId::isValid('req-abc12345'));
    }

    public function testRejectsInvalidTraceId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TraceId::fromString('bad id');
    }
}
