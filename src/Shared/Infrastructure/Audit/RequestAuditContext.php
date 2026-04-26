<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Audit;

use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Tracing\TraceContext;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;

use function is_string;
use function str_starts_with;
use function strtok;
use function trim;

final readonly class RequestAuditContext
{
    public function __construct(
        private ?RequestProviderInterface $requestProvider = null,
        private ?TraceContextProviderInterface $traceContextProvider = null,
    ) {}

    public function actor(
        string $defaultSource = ActorContext::SOURCE_SYSTEM,
        string $defaultActorType = ActorContext::ACTOR_SYSTEM,
        ?string $fallbackUserId = null,
    ): ActorContext {
        if ($this->requestProvider === null) {
            return $this->actorWithoutRequest($defaultSource, $defaultActorType, $fallbackUserId);
        }

        try {
            $request = $this->requestProvider->get();
        } catch (RequestNotSetException) {
            return $this->actorWithoutRequest($defaultSource, $defaultActorType, $fallbackUserId);
        }

        return $this->fromRequest($request, $defaultSource, $defaultActorType, $fallbackUserId);
    }

    public function fromRequest(
        ServerRequestInterface $request,
        string $defaultSource = ActorContext::SOURCE_SYSTEM,
        string $defaultActorType = ActorContext::ACTOR_SYSTEM,
        ?string $fallbackUserId = null,
    ): ActorContext {
        $traceContext = $this->traceContextProvider?->get();
        $source = $this->resolveSource($request, $defaultSource);
        $requestId = $this->stringAttribute($request, RequestAttributes::REQUEST_ID);
        $userId = $this->stringAttribute($request, RequestAttributes::USER_ID) ?? $fallbackUserId;

        return new ActorContext(
            userId: $userId ?? $traceContext?->userId(),
            actorType: $defaultActorType,
            source: $source !== '' ? $source : ($traceContext?->source() ?? $defaultSource),
            requestId: $requestId ?? $traceContext?->requestId(),
            ip: $this->resolveIp($request),
            userAgent: $this->normalizeNullableString($request->getHeaderLine('User-Agent')),
        );
    }

    public function isApiRequest(): bool
    {
        $path = $this->path();
        return $path !== null && str_starts_with($path, '/api');
    }

    public function isDashboardRequest(): bool
    {
        $path = $this->path();
        return $path !== null && str_starts_with($path, '/dashboard');
    }

    public function path(): ?string
    {
        if ($this->requestProvider === null) {
            return null;
        }

        try {
            $request = $this->requestProvider->get();
        } catch (RequestNotSetException) {
            return null;
        }

        $path = trim($request->getUri()->getPath());
        return $path === '' ? null : $path;
    }

    private function resolveSource(ServerRequestInterface $request, string $default): string
    {
        $path = $request->getUri()->getPath();
        if (str_starts_with($path, '/api')) {
            return TraceContext::SOURCE_API;
        }

        if (str_starts_with($path, '/dashboard') || $path === '/login' || $path === '/logout') {
            return TraceContext::SOURCE_WEB;
        }

        return $default;
    }

    private function resolveIp(ServerRequestInterface $request): ?string
    {
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if ($forwardedFor !== '') {
            $firstIp = strtok($forwardedFor, ',');
            if (is_string($firstIp)) {
                return $this->normalizeNullableString($firstIp);
            }
        }

        $serverParams = $request->getServerParams();
        $remoteAddress = $serverParams['REMOTE_ADDR'] ?? null;
        return $this->normalizeNullableString(is_string($remoteAddress) ? $remoteAddress : null);
    }

    private function stringAttribute(ServerRequestInterface $request, string $key): ?string
    {
        return $this->normalizeNullableString($request->getAttribute($key));
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function actorWithoutRequest(
        string $defaultSource,
        string $defaultActorType,
        ?string $fallbackUserId,
    ): ActorContext {
        $traceContext = $this->traceContextProvider?->get();
        $traceSource = $traceContext?->source();
        $source = $traceSource !== null && $traceSource !== '' ? $traceSource : $defaultSource;

        return new ActorContext(
            userId: $traceContext?->userId() ?? $fallbackUserId,
            actorType: $defaultActorType,
            source: $source,
            requestId: $traceContext?->requestId(),
        );
    }
}
