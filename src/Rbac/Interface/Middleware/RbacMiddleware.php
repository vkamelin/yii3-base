<?php

declare(strict_types=1);

namespace App\Rbac\Interface\Middleware;

use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\DashboardAuditAction;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\RequestAttributes;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\ValueObject\UserId;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

use function is_string;
use function strtoupper;
use function trim;
use function str_starts_with;

final readonly class RbacMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     * @param list<string> $webPublicPaths
     * @param array<string, string> $webPermissionsByPrefix
     * @param array<string, string> $apiPermissionsByPrefix
     * @param array<string, string> $apiPermissionsByMethodAndPrefix
     */
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private ResponseFactoryInterface $responseFactory,
        private RedirectResponseFactory $redirectResponseFactory,
        private ApiErrorResponder $apiErrorResponder,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
        private array $apiPrefixes = ['/api', '/api/'],
        private array $webPublicPaths = ['/login', '/dashboard/login'],
        private array $webPermissionsByPrefix = ['/dashboard' => 'dashboard.access'],
        private array $apiPermissionsByPrefix = [],
        private array $apiPermissionsByMethodAndPrefix = [],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isApi = $this->isApiRequest($request);
        if (!$isApi && $this->isPublicWebPath($request->getUri()->getPath())) {
            return $handler->handle($request);
        }

        $permission = $this->requiredPermission(
            $request->getMethod(),
            $request->getUri()->getPath(),
            $isApi,
        );

        if ($permission === null) {
            return $handler->handle($request);
        }

        $userId = $request->getAttribute(RequestAttributes::USER_ID);
        if (!is_string($userId) || $userId === '') {
            if ($isApi) {
                return $this->apiErrorResponder->error($request, 401, 'UNAUTHENTICATED', 'Unauthenticated.');
            }

            return $this->redirectResponseFactory->to($this->loginPathForWebPath($request->getUri()->getPath()));
        }

        try {
            $user = UserId::fromString($userId);
        } catch (InvalidArgumentException) {
            if ($isApi) {
                return $this->apiErrorResponder->error($request, 401, 'UNAUTHENTICATED', 'Unauthenticated.');
            }

            return $this->responseFactory->createResponse(Status::UNAUTHORIZED);
        }

        if (!$this->accessChecker->userHasPermission($user, $permission)) {
            $this->logAccessDenied($request, $permission);
            if ($isApi) {
                return $this->apiErrorResponder->error($request, 403, 'FORBIDDEN', 'Access denied.');
            }

            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        return $handler->handle($request);
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

    private function isPublicWebPath(string $path): bool
    {
        foreach ($this->webPublicPaths as $publicPath) {
            if (trim($publicPath) === $path) {
                return true;
            }
        }

        return false;
    }

    private function loginPathForWebPath(string $path): string
    {
        if (str_starts_with($path, '/dashboard')) {
            return '/dashboard/login';
        }

        return '/login';
    }

    private function requiredPermission(string $method, string $path, bool $isApi): ?string
    {
        if ($isApi) {
            $normalizedMethod = strtoupper($method);
            foreach ($this->apiPermissionsByMethodAndPrefix as $rule => $permission) {
                $delimiterPos = strpos($rule, ' ');
                if ($delimiterPos === false) {
                    continue;
                }

                $ruleMethod = strtoupper(trim(substr($rule, 0, $delimiterPos)));
                $rulePathPrefix = trim(substr($rule, $delimiterPos + 1));

                if ($ruleMethod !== $normalizedMethod || $rulePathPrefix === '') {
                    continue;
                }

                if (str_starts_with($path, $rulePathPrefix)) {
                    return $permission;
                }
            }
        }

        $rules = $isApi ? $this->apiPermissionsByPrefix : $this->webPermissionsByPrefix;
        foreach ($rules as $pathPrefix => $permission) {
            if (str_starts_with($path, $pathPrefix)) {
                return $permission;
            }
        }

        return null;
    }

    private function logAccessDenied(ServerRequestInterface $request, string $permission): void
    {
        $context = $this->auditContext->fromRequest(
            request: $request,
            defaultSource: $this->isApiRequest($request) ? ActorContext::SOURCE_API : ActorContext::SOURCE_WEB,
            defaultActorType: ActorContext::ACTOR_USER,
        );

        $this->activityLogger->log(ActivityLogEntry::admin(
            action: DashboardAuditAction::ACCESS_DENIED,
            actorUserId: $context->userId,
            entityType: 'permission',
            payload: [
                'permission' => $permission,
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ],
            context: $context,
        ));
    }
}
