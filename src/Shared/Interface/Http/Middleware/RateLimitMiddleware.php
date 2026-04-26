<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\ApiAuditAction;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\RateLimit\RateLimiterInterface;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

use function sha1;
use function sprintf;
use function str_starts_with;
use function strtok;

final readonly class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     */
    public function __construct(
        private RateLimiterInterface $rateLimiter,
        private ApiErrorResponder $apiErrorResponder,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
        private TraceContextProviderInterface $traceContextProvider,
        private LoggerInterface $logger,
        private int $limit = 60,
        private int $windowSeconds = 60,
        private string $keyPrefix = 'rate_limit:api:',
        private array $apiPrefixes = ['/api', '/api/'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiRequest($request)) {
            return $handler->handle($request);
        }

        $rawKey = sprintf(
            '%s|%s|%s',
            $request->getMethod(),
            $request->getUri()->getPath(),
            $this->resolveIdentifier($request),
        );

        $result = $this->rateLimiter->hit(
            key: $this->keyPrefix . sha1($rawKey),
            limit: $this->limit,
            windowSeconds: $this->windowSeconds,
        );

        if ($result->isLimited($this->limit)) {
            $traceContext = $this->traceContextProvider->get();
            $context = $this->auditContext->fromRequest(
                request: $request,
                defaultSource: ActorContext::SOURCE_API,
                defaultActorType: ActorContext::ACTOR_GUEST,
            );
            $this->activityLogger->log(ActivityLogEntry::api(
                action: ApiAuditAction::RATE_LIMIT_EXCEEDED,
                actorUserId: $context->userId,
                entityType: 'api_request',
                payload: [
                    'method' => $request->getMethod(),
                    'path' => $request->getUri()->getPath(),
                    'retry_after' => $result->retryAfter,
                ],
                context: $context,
            ));

            $this->logger->warning('api.rate_limit.exceeded', [
                'type' => 'rate_limit',
                'request_id' => $traceContext->requestId(),
                'correlation_id' => $traceContext->correlationId(),
                'method' => $request->getMethod(),
                'path' => $request->getUri()->getPath(),
                'retry_after' => $result->retryAfter,
                'source' => ActorContext::SOURCE_API,
            ]);

            return $this->apiErrorResponder->error(
                request: $request,
                statusCode: 429,
                code: 'RATE_LIMIT_EXCEEDED',
                message: 'Rate limit exceeded.',
                headers: [
                    'Retry-After' => (string) $result->retryAfter,
                ],
            );
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining);
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        foreach ($this->apiPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function resolveIdentifier(ServerRequestInterface $request): string
    {
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if ($forwardedFor !== '') {
            $firstIp = strtok($forwardedFor, ',');
            if ($firstIp !== false) {
                return $firstIp;
            }
        }

        $remoteAddress = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        if (is_string($remoteAddress) && $remoteAddress !== '') {
            return $remoteAddress;
        }

        return 'anonymous';
    }
}
