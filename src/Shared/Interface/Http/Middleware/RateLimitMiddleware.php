<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\RateLimit\RateLimiterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
