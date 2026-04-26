<?php

declare(strict_types=1);

namespace App\Rbac\Domain\ValueObject;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final readonly class PermissionId
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException('Invalid permission ID.');
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
