<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function is_string;
use function microtime;
use function round;

final readonly class AccessLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startedAt = microtime(true);

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $this->logger->error('HTTP request failed.', $this->context($request, null, $startedAt, $e));
            throw $e;
        }

        $this->logger->info('HTTP request completed.', $this->context($request, $response, $startedAt));

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(
        ServerRequestInterface $request,
        ?ResponseInterface $response,
        float $startedAt,
        ?Throwable $error = null,
    ): array {
        $requestId = $request->getAttribute(RequestAttributes::REQUEST_ID);

        return [
            'request_id' => is_string($requestId) && $requestId !== '' ? $requestId : null,
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'status' => $response?->getStatusCode(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 3),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
            'user_agent' => $request->getHeaderLine('User-Agent') ?: null,
            'error' => $error?->getMessage(),
        ];
    }
}
