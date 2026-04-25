<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Ramsey\Uuid\Uuid;

final class UuidBinary
{
    public static function new(): string
    {
        return Uuid::uuid7()->getBytes();
    }

    public static function fromString(string $uuid): string
    {
        return Uuid::fromString($uuid)->getBytes();
    }

    public static function toString(string $bytes): string
    {
        return Uuid::fromBytes($bytes)->toString();
    }
}
