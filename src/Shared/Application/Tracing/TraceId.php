<?php

declare(strict_types=1);

namespace App\Shared\Application\Tracing;

use InvalidArgumentException;

use function preg_match;
use function strlen;

final readonly class TraceId
{
    private const MAX_LENGTH = 128;
    private const MIN_LENGTH = 8;
    private const PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._:-]{7,127}$/';

    public function __construct(
        private string $value,
    ) {
        if (!self::isValid($this->value)) {
            throw new InvalidArgumentException('Invalid trace id format.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        $length = strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return false;
        }

        return preg_match(self::PATTERN, $value) === 1;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
