<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\RateLimit;

final readonly class RateLimitResult
{
    public function __construct(
        public int $attempts,
        public int $remaining,
        public int $retryAfter,
    ) {}

    public function isLimited(int $limit): bool
    {
        return $this->attempts > $limit;
    }
}
