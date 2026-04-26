<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Interface\Middleware;

use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\Rbac\Interface\Middleware\RbacMiddleware;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\RequestAttributes;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\ValueObject\UserId;
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

final class RbacMiddlewareTest extends Unit
{
    public function testReturns403WhenPermissionMissing(): void
    {
        $responseFactory = new ResponseFactory();
        $middleware = new RbacMiddleware(
            accessChecker: new class implements AccessCheckerInterface {
                public function userHasPermission(UserId $userId, string $permissionCode): bool
                {
                    return false;
                }

                public function userHasAnyPermission(UserId $userId, array $permissionCodes): bool
                {
                    return false;
                }

                public function userHasAllPermissions(UserId $userId, array $permissionCodes): bool
                {
                    return false;
                }
            },
            responseFactory: $responseFactory,
            redirectResponseFactory: new RedirectResponseFactory($responseFactory),
            apiErrorResponder: new ApiErrorResponder($responseFactory, new StreamFactory()),
            activityLogger: $this->createNullActivityLogger(),
            auditContext: new RequestAuditContext(new RequestProvider()),
            apiPermissionsByPrefix: [
                '/api/v1/secure' => 'secure.read',
            ],
        );

        $request = (new ServerRequest(uri: '/api/v1/secure/data'))
            ->withAttribute(RequestAttributes::REQUEST_ID, 'req-403')
            ->withAttribute(RequestAttributes::USER_ID, '3fa85f64-5717-4562-b3fc-2c963f66afa6');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        assertSame(403, $response->getStatusCode());
    }

    public function testDashboardActivityLogDeniedWithoutPermission(): void
    {
        $responseFactory = new ResponseFactory();
        $middleware = new RbacMiddleware(
            accessChecker: new class implements AccessCheckerInterface {
                public function userHasPermission(UserId $userId, string $permissionCode): bool
                {
                    return false;
                }

                public function userHasAnyPermission(UserId $userId, array $permissionCodes): bool
                {
                    return false;
                }

                public function userHasAllPermissions(UserId $userId, array $permissionCodes): bool
                {
                    return false;
                }
            },
            responseFactory: $responseFactory,
            redirectResponseFactory: new RedirectResponseFactory($responseFactory),
            apiErrorResponder: new ApiErrorResponder($responseFactory, new StreamFactory()),
            activityLogger: $this->createNullActivityLogger(),
            auditContext: new RequestAuditContext(new RequestProvider()),
            webPermissionsByPrefix: [
                '/dashboard/activity-log' => 'activity_log.view',
            ],
        );

        $request = (new ServerRequest(uri: '/dashboard/activity-log'))
            ->withAttribute(RequestAttributes::REQUEST_ID, 'req-web-403')
            ->withAttribute(RequestAttributes::USER_ID, '3fa85f64-5717-4562-b3fc-2c963f66afa6');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        assertSame(403, $response->getStatusCode());
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
