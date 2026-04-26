<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Interface\Http\Middleware;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\Middleware\RateLimitMiddleware;
use App\Shared\Interface\Http\RateLimit\RateLimitResult;
use App\Shared\Interface\Http\RateLimit\RateLimiterInterface;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use Codeception\Test\Unit;
use HttpSoft\Message\Response;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\RequestProvider\RequestProvider;

use function PHPUnit\Framework\assertSame;

final class RateLimitMiddlewareTest extends Unit
{
    public function testReturns429WhenLimitExceeded(): void
    {
        $responseFactory = new ResponseFactory();
        $middleware = new RateLimitMiddleware(
            rateLimiter: new class implements RateLimiterInterface {
                public function hit(string $key, int $limit, int $windowSeconds): RateLimitResult
                {
                    return new RateLimitResult(
                        attempts: $limit + 1,
                        remaining: 0,
                        retryAfter: 30,
                    );
                }
            },
            apiErrorResponder: new ApiErrorResponder($responseFactory, new StreamFactory()),
            activityLogger: $this->createNullActivityLogger(),
            auditContext: new RequestAuditContext(new RequestProvider()),
            limit: 3,
            windowSeconds: 60,
        );

        $request = (new ServerRequest(uri: '/api/v1/auth/me'))
            ->withAttribute('request_id', 'req-429');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        assertSame(429, $response->getStatusCode());
        assertSame('30', $response->getHeaderLine('Retry-After'));
    }

    private function createNullActivityLogger(): ActivityLoggerInterface
    {
        return new class implements ActivityLoggerInterface {
            public function log(ActivityLogEntry $entry): void
            {
            }
        };
    }
}
