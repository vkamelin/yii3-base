<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Logging;

use App\Shared\Infrastructure\Logging\JsonLogFormatter;
use Codeception\Test\Unit;
use Monolog\Level;
use Monolog\LogRecord;

use function is_array;
use function json_decode;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class JsonLogFormatterTest extends Unit
{
    public function testFormatsLogRecordAsJsonLine(): void
    {
        $formatter = new JsonLogFormatter();
        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            channel: 'test',
            level: Level::Info,
            message: 'hello',
            context: ['request_id' => 'req-1'],
            extra: [],
        );

        $json = $formatter->format($record);
        $decoded = json_decode($json, true);

        assertTrue(is_array($decoded));
        assertSame('test', $decoded['channel']);
        assertSame('hello', $decoded['message']);
        assertSame('req-1', $decoded['context']['request_id']);
    }
}
