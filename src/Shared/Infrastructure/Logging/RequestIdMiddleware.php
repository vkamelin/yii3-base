<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use App\Shared\Application\Tracing\TraceId;
use App\Shared\Infrastructure\Tracing\TraceIdGenerator;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function trim;

final readonly class RequestIdMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TraceIdGenerator $traceIdGenerator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $this->resolveTraceId($request->getHeaderLine('X-Request-Id'))
            ?? $this->traceIdGenerator->generate()->toString();
        $correlationId = $this->resolveTraceId($request->getHeaderLine('X-Correlation-Id')) ?? $requestId;

        $request = $request
            ->withAttribute(RequestAttributes::REQUEST_ID, $requestId)
            ->withAttribute(RequestAttributes::CORRELATION_ID, $correlationId);

        $response = $handler->handle($request);

        if (!$response->hasHeader('X-Request-Id')) {
            $response = $response->withHeader('X-Request-Id', $requestId);
        }

        if (!$response->hasHeader('X-Correlation-Id')) {
            $response = $response->withHeader('X-Correlation-Id', $correlationId);
        }

        return $response;
    }

    private function resolveTraceId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || !TraceId::isValid($value)) {
            return null;
        }

        return $value;
    }
}
