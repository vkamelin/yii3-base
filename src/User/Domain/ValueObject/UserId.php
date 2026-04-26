<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class UserId
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        return new self($value);
    }

    public static function new(): self
    {
        return new self(Uuid::uuid7()->toString());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $id): bool
    {
        return $this->value === $id->value;
    }
}
