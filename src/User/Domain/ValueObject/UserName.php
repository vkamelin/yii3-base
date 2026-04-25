<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use InvalidArgumentException;

use function mb_strlen;
use function trim;

final readonly class UserName
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('User name cannot be empty.');
        }

        if (mb_strlen($value) > 160) {
            throw new InvalidArgumentException('User name is too long.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
