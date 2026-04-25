<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\RateLimit;

interface RateLimiterInterface
{
    public function hit(string $key, int $limit, int $windowSeconds): RateLimitResult;
}
