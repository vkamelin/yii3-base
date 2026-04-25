<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use InvalidArgumentException;

use function filter_var;
use function mb_strtolower;
use function mb_strlen;
use function trim;

use const FILTER_VALIDATE_EMAIL;

final readonly class Email
{
    private string $value;
    private string $normalized;

    private function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email.');
        }

        if (mb_strlen($value) > 320) {
            throw new InvalidArgumentException('Email is too long.');
        }

        $this->value = $value;
        $this->normalized = mb_strtolower($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function normalized(): string
    {
        return $this->normalized;
    }

    public function equals(self $other): bool
    {
        return $this->normalized === $other->normalized;
    }
}
