<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Queue\JobRegistry;
use App\Shared\Infrastructure\Queue\JobSerializer;
use App\Shared\Infrastructure\Queue\MySqlQueue;
use App\Shared\Infrastructure\Queue\QueueInterface;
use App\Shared\Infrastructure\Queue\RedisQueue;
use App\Shared\Infrastructure\Queue\Worker;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    JobRegistry::class => [
        '__construct()' => [
            'definitions' => $params['queue']['jobs'],
        ],
    ],

    JobSerializer::class => JobSerializer::class,
    MySqlQueue::class => [
        '__construct()' => [
            'defaultMaxAttempts' => $params['queue']['defaultMaxAttempts'],
        ],
    ],
    RedisQueue::class => [
        '__construct()' => [
            'defaultMaxAttempts' => $params['queue']['defaultMaxAttempts'],
            'keyPrefix' => $params['queue']['redisKeyPrefix'],
        ],
    ],
    QueueInterface::class => Reference::to(MySqlQueue::class),
    Worker::class => Worker::class,
];
