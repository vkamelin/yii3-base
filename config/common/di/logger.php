<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

/** @var array $params */

return [
    LoggerInterface::class => static function () use ($params): LoggerInterface {
        $logFile = rtrim($params['app']['runtimePath'], '/\\') . '/logs/app.log';

        $directory = dirname($logFile);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create log directory: %s', $directory));
        }

        $logger = new Logger($params['app']['name']);
        $logger->pushHandler(new StreamHandler($logFile, Level::Debug));

        return $logger;
    },
];
