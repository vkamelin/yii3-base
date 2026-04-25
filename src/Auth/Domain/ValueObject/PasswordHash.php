<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

use InvalidArgumentException;

use function mb_strlen;

final readonly class PasswordHash
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new InvalidArgumentException('Password hash cannot be empty.');
        }

        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Password hash is too long.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
