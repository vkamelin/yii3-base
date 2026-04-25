<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

use InvalidArgumentException;

use function hash;
use function preg_match;
use function strtolower;
use function substr;

final readonly class TokenHash
{
    private const LENGTH = 32;

    private function __construct(
        private string $value,
    ) {
    }

    public static function fromHex(string $value): self
    {
        $value = strtolower($value);
        if (preg_match('/^[a-f0-9]{32}$/', $value) !== 1) {
            throw new InvalidArgumentException('Invalid token hash.');
        }

        return new self($value);
    }

    public static function fromPlainToken(string $token): self
    {
        if ($token === '') {
            throw new InvalidArgumentException('Token cannot be empty.');
        }

        return new self(substr(hash('sha256', $token), 0, self::LENGTH));
    }

    public function value(): string
    {
        return $this->value;
    }
}
