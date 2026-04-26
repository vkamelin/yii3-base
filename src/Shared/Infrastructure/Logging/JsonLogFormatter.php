<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use DateTimeInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use Stringable;
use Throwable;

use function array_map;
use function implode;
use function is_array;
use function is_scalar;
use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class JsonLogFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        $payload = [
            'timestamp' => $record->datetime->format(DateTimeInterface::ATOM),
            'level' => $record->level->getName(),
            'channel' => $record->channel,
            'message' => $record->message,
            'context' => $this->normalize($record->context),
            'extra' => $this->normalize($record->extra),
        ];

        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n";
    }

    /**
     * @param array<array-key, LogRecord> $records
     */
    public function formatBatch(array $records): string
    {
        return implode('', array_map($this->format(...), $records));
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->normalize($item);
            }

            return $result;
        }

        if ($value instanceof Throwable) {
            return [
                'exception' => $value::class,
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
