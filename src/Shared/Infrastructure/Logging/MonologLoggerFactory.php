<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function dirname;
use function is_dir;
use function mkdir;
use function rtrim;
use function sprintf;

final readonly class MonologLoggerFactory
{
    public function __construct(
        private string $runtimePath,
        private JsonLogFormatter $jsonLogFormatter,
    ) {}

    public function create(string $channel, string $fileName, Level $level): LoggerInterface
    {
        $logFile = rtrim($this->runtimePath, '/\\') . '/logs/' . $fileName;
        $directory = dirname($logFile);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create log directory: %s', $directory));
        }

        $logger = new Logger($channel);

        $handler = new StreamHandler($logFile, $level);
        $handler->setFormatter($this->jsonLogFormatter);
        $logger->pushHandler($handler);

        return $logger;
    }
}
