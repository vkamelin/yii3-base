<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

use InvalidArgumentException;

use function mb_strlen;

final readonly class PlainPassword
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new InvalidArgumentException('Password cannot be empty.');
        }

        if (mb_strlen($value) < 8) {
            throw new InvalidArgumentException('Password must contain at least 8 characters.');
        }

        if (mb_strlen($value) > 4096) {
            throw new InvalidArgumentException('Password is too long.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
