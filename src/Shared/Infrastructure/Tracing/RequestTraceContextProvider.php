<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Tracing;

use App\Shared\Application\Tracing\TraceContext;
use App\Shared\Application\Tracing\TraceContextInterface;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Application\Tracing\TraceId;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;

use function is_string;
use function str_starts_with;
use function trim;

final readonly class RequestTraceContextProvider implements TraceContextProviderInterface
{
    public function __construct(
        private RequestProviderInterface $requestProvider,
        private TraceIdGenerator $traceIdGenerator,
    ) {}

    public function get(): TraceContextInterface
    {
        try {
            $request = $this->requestProvider->get();
        } catch (RequestNotSetException) {
            return new TraceContext(
                requestId: $this->traceIdGenerator->generate()->toString(),
                correlationId: null,
                userId: null,
                source: TraceContext::SOURCE_SYSTEM,
            );
        }

        return $this->fromRequest($request);
    }

    private function fromRequest(ServerRequestInterface $request): TraceContextInterface
    {
        $requestId = $this->normalizeTraceId(
            $request->getAttribute(RequestAttributes::REQUEST_ID),
            $request->getHeaderLine('X-Request-Id'),
        ) ?? $this->traceIdGenerator->generate()->toString();

        $correlationId = $this->normalizeTraceId(
            $request->getAttribute(RequestAttributes::CORRELATION_ID),
            $request->getHeaderLine('X-Correlation-Id'),
        );

        $userId = $this->normalizeNullableString($request->getAttribute(RequestAttributes::USER_ID));

        return new TraceContext(
            requestId: $requestId,
            correlationId: $correlationId,
            userId: $userId,
            source: $this->resolveSource($request),
        );
    }

    private function resolveSource(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();

        if (str_starts_with($path, '/api')) {
            return TraceContext::SOURCE_API;
        }

        if (str_starts_with($path, '/dashboard') || $path === '/login' || $path === '/logout') {
            return TraceContext::SOURCE_WEB;
        }

        return TraceContext::SOURCE_SYSTEM;
    }

    private function normalizeTraceId(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $normalized = $this->normalizeNullableString($value);
            if ($normalized !== null && TraceId::isValid($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
