<?php

declare(strict_types=1);

use App\Shared\Infrastructure\Logging\MonologLoggerFactory;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Yiisoft\Definitions\Reference;

return [
    'logger.console' => static fn(MonologLoggerFactory $factory): LoggerInterface => $factory->create(
        channel: 'console',
        fileName: 'console.log',
        level: Level::Info,
    ),
    LoggerInterface::class => Reference::to('logger.console'),
];
