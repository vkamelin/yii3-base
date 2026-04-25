<?php

declare(strict_types=1);

namespace App\Rbac\Domain\ValueObject;

use InvalidArgumentException;

final readonly class PermissionCode
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('Permission code cannot be empty.');
        }

        if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $value)) {
            throw new InvalidArgumentException('Invalid permission code.');
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
