<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use App\Auth\Application\Command\GetAuthenticatedUserCommand;
use App\Auth\Application\Handler\GetAuthenticatedUserHandler;
use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function in_array;
use function is_string;
use function str_starts_with;
use function trim;

final readonly class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     * @param list<string> $apiPublicPaths
     * @param list<string> $webProtectedPrefixes
     * @param list<string> $webPublicPaths
     */
    public function __construct(
        private AuthSessionInterface $authSession,
        private BearerTokenExtractor $tokenExtractor,
        private GetAuthenticatedUserHandler $getAuthenticatedUserHandler,
        private ApiErrorResponder $apiErrorResponder,
        private RedirectResponseFactory $redirectResponseFactory,
        private array $apiPrefixes = ['/api', '/api/'],
        private array $apiPublicPaths = ['/api/v1/auth/login'],
        private array $webProtectedPrefixes = ['/dashboard'],
        private array $webPublicPaths = ['/login', '/dashboard/login'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isApiRequest($request)) {
            return $this->processApi($request, $handler);
        }

        return $this->processWeb($request, $handler);
    }

    private function processApi(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getUri()->getPath(), $this->apiPublicPaths, true)) {
            return $handler->handle($request);
        }

        $token = $this->tokenExtractor->extract($request);
        if ($token === null) {
            return $this->apiErrorResponder->error($request, 401, 'UNAUTHORIZED', 'Missing bearer token.');
        }

        try {
            $result = $this->getAuthenticatedUserHandler->handle(new GetAuthenticatedUserCommand($token));
        } catch (AccessDeniedException $e) {
            return $this->apiErrorResponder->error($request, 403, 'FORBIDDEN', $e->getMessage() ?: 'Forbidden.');
        } catch (InvalidCredentialsException|ValidationException) {
            return $this->apiErrorResponder->error($request, 401, 'UNAUTHORIZED', 'Invalid bearer token.');
        }

        $request = $request
            ->withAttribute(RequestAttributes::USER_ID, $result->userId)
            ->withAttribute(RequestAttributes::AUTH_CHANNEL, 'api')
            ->withAttribute(RequestAttributes::API_TOKEN, $token);

        return $handler->handle($request);
    }

    private function processWeb(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $sessionUserId = $this->authSession->userId();

        if ($this->isProtectedWebPath($path) && !$this->isPublicWebPath($path) && $sessionUserId === null) {
            return $this->redirectResponseFactory->to($this->loginPathForWebPath($path));
        }

        if (is_string($sessionUserId) && $sessionUserId !== '') {
            $request = $request
                ->withAttribute(RequestAttributes::USER_ID, $sessionUserId)
                ->withAttribute(RequestAttributes::AUTH_CHANNEL, 'web');
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

    private function isProtectedWebPath(string $path): bool
    {
        foreach ($this->webProtectedPrefixes as $prefix) {
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
}
