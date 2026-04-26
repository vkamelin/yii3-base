<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function is_int;

final readonly class ErrorLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private LogContext $logContext,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (Throwable $throwable) {
            $this->logger->error('error', $this->logContext->error($request, $throwable, $this->resolveStatusCode($throwable)));
            throw $throwable;
        }

        if ($response->getStatusCode() >= 500) {
            $this->logger->error(
                'error',
                $this->logContext->error(
                    $request,
                    new RuntimeException('HTTP request finished with server error status.'),
                    $response->getStatusCode(),
                ),
            );
        }

        return $response;
    }

    private function resolveStatusCode(Throwable $throwable): int
    {
        $code = $throwable->getCode();
        if (is_int($code) && $code >= 400 && $code <= 599) {
            return $code;
        }

        return 500;
    }
}
