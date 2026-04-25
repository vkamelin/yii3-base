<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use App\Shared\Infrastructure\Queue\Exception\QueueException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class QueueJobId
{
    private function __construct(
        private string $value,
        private string $binary,
    ) {}

    public static function generate(): self
    {
        return self::fromUuid(Uuid::uuid7());
    }

    public static function fromString(string $value): self
    {
        try {
            return self::fromUuid(Uuid::fromString($value));
        } catch (\Throwable $e) {
            throw new QueueException('Invalid queue job UUID string.', 0, $e);
        }
    }

    public static function fromBinary(string $binary): self
    {
        if (strlen($binary) !== 16) {
            throw new QueueException('Invalid queue job UUID binary length.');
        }

        try {
            return self::fromUuid(Uuid::fromBytes($binary));
        } catch (\Throwable $e) {
            throw new QueueException('Invalid queue job UUID binary value.', 0, $e);
        }
    }

    public function toBinary(): string
    {
        return $this->binary;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function fromUuid(UuidInterface $uuid): self
    {
        return new self($uuid->toString(), $uuid->getBytes());
    }
}

