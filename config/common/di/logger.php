<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Logging\MonologLoggerFactory;
use App\Shared\Infrastructure\Logging\JsonLogFormatter;
use Monolog\Level;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    JsonLogFormatter::class => JsonLogFormatter::class,

    MonologLoggerFactory::class => [
        'class' => MonologLoggerFactory::class,
        '__construct()' => [
            'runtimePath' => $params['app']['runtimePath'],
            'jsonLogFormatter' => Reference::to(JsonLogFormatter::class),
        ],
    ],

    'logger.app' => static fn(MonologLoggerFactory $factory): \Psr\Log\LoggerInterface => $factory->create(
        channel: 'app',
        fileName: 'app.log',
        level: Level::Info,
    ),
    'logger.access' => static fn(MonologLoggerFactory $factory): \Psr\Log\LoggerInterface => $factory->create(
        channel: 'access',
        fileName: 'access.log',
        level: Level::Info,
    ),
    'logger.error' => static fn(MonologLoggerFactory $factory): \Psr\Log\LoggerInterface => $factory->create(
        channel: 'error',
        fileName: 'error.log',
        level: Level::Error,
    ),
];
