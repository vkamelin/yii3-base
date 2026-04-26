<?php

declare(strict_types=1);

use Predis\Client;
use Predis\ClientInterface;
use App\Shared\Interface\Http\RateLimit\RateLimiterInterface;
use App\Shared\Infrastructure\Http\RateLimit\RedisRateLimiter;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Redis\RedisCache;

/** @var array $params */

return [
    ClientInterface::class => static function () use ($params): ClientInterface {
        $client = new Client([
            'scheme' => 'tcp',
            'host' => $params['redis']['host'],
            'port' => (int) $params['redis']['port'],
            'password' => $params['redis']['password'] !== '' ? $params['redis']['password'] : null,
            'database' => (int) $params['redis']['database'],
            'timeout' => (float) $params['redis']['timeout'],
        ]);

        try {
            $client->connect();
            $client->ping();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to connect to Redis.', 0, $e);
        }

        return $client;
    },

    CacheInterface::class => static fn(ClientInterface $redis): RedisCache => new RedisCache($redis),

    RateLimiterInterface::class => RedisRateLimiter::class,
];
