<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class AccessLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private LogContext $logContext,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startedAt = hrtime(true);
        $response = $handler->handle($request);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $this->logger->info('access', $this->logContext->access($request, $response->getStatusCode(), $durationMs));

        return $response;
    }
}
