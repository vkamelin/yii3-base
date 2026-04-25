<?php

declare(strict_types=1);

namespace App\Rbac\Interface\Middleware;

use App\Auth\Application\Session\AuthSessionInterface;
use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\User\Domain\ValueObject\UserId;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

use function trim;

final readonly class RbacMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AuthSessionInterface $authSession,
        private ResponseFactoryInterface $responseFactory,
        private string $permission,
    ) {
        if (trim($this->permission) === '') {
            throw new InvalidArgumentException('Permission code cannot be empty.');
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = $this->authSession->userId();
        if ($userId === null) {
            return $this->responseFactory->createResponse(Status::UNAUTHORIZED);
        }

        try {
            $user = UserId::fromString($userId);
        } catch (InvalidArgumentException) {
            return $this->responseFactory->createResponse(Status::UNAUTHORIZED);
        }

        if (!$this->accessChecker->userHasPermission($user, $this->permission)) {
            // TODO: unify API JSON errors and Web redirects via dedicated error factory.
            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        return $handler->handle($request);
    }
}
