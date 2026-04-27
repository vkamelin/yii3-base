<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use App\Shared\Application\Tracing\TraceContext;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Router\CurrentRoute;

use function is_string;
use function memory_get_peak_usage;
use function round;
use function str_starts_with;
use function strtok;
use function trim;

final readonly class LogContext
{
    public function __construct(
        private TraceContextProviderInterface $traceContextProvider,
        private LogContextSanitizer $sanitizer,
        private CurrentRoute $currentRoute,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function access(ServerRequestInterface $request, int $status, float $durationMs): array
    {
        $trace = $this->traceContextProvider->get();

        return $this->sanitizer->sanitize([
            'type' => 'access',
            'request_id' => $trace->requestId() ?? $this->stringAttribute($request, RequestAttributes::REQUEST_ID),
            'correlation_id' => $trace->correlationId()
                ?? $this->stringAttribute($request, RequestAttributes::CORRELATION_ID),
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'route' => $this->currentRoute->getName(),
            'status' => $status,
            'duration_ms' => round($durationMs, 3),
            'user_id' => $trace->userId() ?? $this->stringAttribute($request, RequestAttributes::USER_ID),
            'ip' => $this->resolveIp($request),
            'user_agent' => $this->normalizeNullableString($request->getHeaderLine('User-Agent')),
            'source' => $trace->source() !== '' ? $trace->source() : $this->resolveSource($request),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function error(ServerRequestInterface $request, Throwable $exception, int $status): array
    {
        $trace = $this->traceContextProvider->get();

        return $this->sanitizer->sanitize([
            'type' => 'error',
            'request_id' => $trace->requestId() ?? $this->stringAttribute($request, RequestAttributes::REQUEST_ID),
            'correlation_id' => $trace->correlationId()
                ?? $this->stringAttribute($request, RequestAttributes::CORRELATION_ID),
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'status' => $status,
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'route' => $this->currentRoute->getName(),
            'user_id' => $trace->userId() ?? $this->stringAttribute($request, RequestAttributes::USER_ID),
            'source' => $trace->source() !== '' ? $trace->source() : $this->resolveSource($request),
            'trace' => $exception->getTraceAsString(),
        ]);
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

    private function resolveIp(ServerRequestInterface $request): ?string
    {
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if ($forwardedFor !== '') {
            $firstIp = strtok($forwardedFor, ',');
            return $this->normalizeNullableString($firstIp);
        }

        $serverParams = $request->getServerParams();
        return $this->normalizeNullableString($serverParams['REMOTE_ADDR'] ?? null);
    }

    private function stringAttribute(ServerRequestInterface $request, string $name): ?string
    {
        return $this->normalizeNullableString($request->getAttribute($name));
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
