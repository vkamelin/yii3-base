<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Audit;

use App\Shared\Application\Audit\ActorContext;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;

use function is_array;
use function is_string;
use function str_starts_with;
use function strtok;
use function trim;

final readonly class RequestAuditContext
{
    public function __construct(
        private RequestProviderInterface $requestProvider,
    ) {
    }

    public function actor(
        string $defaultSource = ActorContext::SOURCE_SYSTEM,
        string $defaultActorType = ActorContext::ACTOR_SYSTEM,
        ?string $fallbackUserId = null,
    ): ActorContext {
        try {
            $request = $this->requestProvider->get();
        } catch (RequestNotSetException) {
            return new ActorContext($fallbackUserId, $defaultActorType, $defaultSource);
        }

        return $this->fromRequest($request, $defaultSource, $defaultActorType, $fallbackUserId);
    }

    public function fromRequest(
        ServerRequestInterface $request,
        string $defaultSource = ActorContext::SOURCE_SYSTEM,
        string $defaultActorType = ActorContext::ACTOR_SYSTEM,
        ?string $fallbackUserId = null,
    ): ActorContext {
        $source = $this->resolveSource($request, $defaultSource);
        $requestId = $this->stringAttribute($request, RequestAttributes::REQUEST_ID);
        $userId = $this->stringAttribute($request, RequestAttributes::USER_ID) ?? $fallbackUserId;

        return new ActorContext(
            userId: $userId,
            actorType: $defaultActorType,
            source: $source,
            requestId: $requestId,
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
            return ActorContext::SOURCE_API;
        }

        if (str_starts_with($path, '/dashboard') || $path === '/login' || $path === '/logout') {
            return ActorContext::SOURCE_WEB;
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
        if (!is_array($serverParams)) {
            return null;
        }

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
}
