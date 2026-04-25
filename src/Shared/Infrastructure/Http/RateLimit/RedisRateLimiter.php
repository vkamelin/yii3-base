<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\RateLimit;

use App\Shared\Interface\Http\RateLimit\RateLimitResult;
use App\Shared\Interface\Http\RateLimit\RateLimiterInterface;
use Predis\ClientInterface;
use Throwable;

use function intdiv;
use function max;
use function time;

final readonly class RedisRateLimiter implements RateLimiterInterface
{
    public function __construct(
        private ClientInterface $redis,
    ) {}

    public function hit(string $key, int $limit, int $windowSeconds): RateLimitResult
    {
        $now = time();
        $windowStartedAt = intdiv($now, $windowSeconds) * $windowSeconds;
        $redisKey = $key . ':' . $windowStartedAt;

        try {
            $attempts = $this->redis->incr($redisKey);
            if ($attempts === 1) {
                $this->redis->expire($redisKey, $windowSeconds + 1);
            }
        } catch (Throwable) {
            // Fail-open to preserve availability if Redis is unavailable.
            return new RateLimitResult(attempts: 0, remaining: $limit, retryAfter: 0);
        }

        $remaining = max(0, $limit - $attempts);
        $retryAfter = $attempts > $limit ? max(1, ($windowStartedAt + $windowSeconds) - $now) : 0;

        return new RateLimitResult(
            attempts: $attempts,
            remaining: $remaining,
            retryAfter: $retryAfter,
        );
    }
}
