<?php

declare(strict_types=1);

namespace App\Rbac\Domain\ValueObject;

use InvalidArgumentException;

final readonly class RoleCode
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('Role code cannot be empty.');
        }

        if (!preg_match('/^[a-z][a-z0-9_.-]{1,79}$/', $value)) {
            throw new InvalidArgumentException('Invalid role code.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $code): bool
    {
        return $this->value === $code->value;
    }
}
