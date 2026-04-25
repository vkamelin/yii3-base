<?php

declare(strict_types=1);

namespace App\Rbac\Interface\Middleware;

use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\RequestAttributes;
use App\User\Domain\ValueObject\UserId;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

use function is_string;
use function str_starts_with;

final readonly class RbacMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     * @param array<string, string> $webPermissionsByPrefix
     * @param array<string, string> $apiPermissionsByPrefix
     */
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private ResponseFactoryInterface $responseFactory,
        private RedirectResponseFactory $redirectResponseFactory,
        private ApiErrorResponder $apiErrorResponder,
        private array $apiPrefixes = ['/api', '/api/'],
        private array $webPermissionsByPrefix = ['/dashboard' => 'dashboard.view'],
        private array $apiPermissionsByPrefix = [],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isApi = $this->isApiRequest($request);
        $permission = $this->requiredPermission($request->getUri()->getPath(), $isApi);

        if ($permission === null) {
            return $handler->handle($request);
        }

        $userId = $request->getAttribute(RequestAttributes::USER_ID);
        if (!is_string($userId) || $userId === '') {
            if ($isApi) {
                return $this->apiErrorResponder->error($request, 401, 'UNAUTHORIZED', 'Unauthorized.');
            }

            return $this->redirectResponseFactory->to('/login');
        }

        try {
            $user = UserId::fromString($userId);
        } catch (InvalidArgumentException) {
            if ($isApi) {
                return $this->apiErrorResponder->error($request, 401, 'UNAUTHORIZED', 'Unauthorized.');
            }

            return $this->responseFactory->createResponse(Status::UNAUTHORIZED);
        }

        if (!$this->accessChecker->userHasPermission($user, $permission)) {
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

    private function requiredPermission(string $path, bool $isApi): ?string
    {
        $rules = $isApi ? $this->apiPermissionsByPrefix : $this->webPermissionsByPrefix;
        foreach ($rules as $pathPrefix => $permission) {
            if (str_starts_with($path, $pathPrefix)) {
                return $permission;
            }
        }

        return null;
    }
}
