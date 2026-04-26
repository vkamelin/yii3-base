<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\Middleware;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Auth\Application\Command\GetAuthenticatedUserCommand;
use App\Auth\Application\Handler\GetAuthenticatedUserHandler;
use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function in_array;
use function str_starts_with;

final readonly class BearerTokenMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     * @param list<string> $publicPaths
     */
    public function __construct(
        private BearerTokenExtractor $tokenExtractor,
        private GetAuthenticatedUserHandler $getAuthenticatedUserHandler,
        private ApiErrorResponseFactory $errorResponseFactory,
        private array $apiPrefixes = ['/api', '/api/'],
        private array $publicPaths = ['/api/v1/auth/login'],
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiRequest($request)) {
            return $handler->handle($request);
        }

        if (in_array($request->getUri()->getPath(), $this->publicPaths, true)) {
            return $handler->handle($request);
        }

        $token = $this->tokenExtractor->extract($request);
        if ($token === null) {
            return $this->errorResponseFactory->unauthenticated($request, 'Missing bearer token.');
        }

        try {
            $authResult = $this->getAuthenticatedUserHandler->handle(new GetAuthenticatedUserCommand($token));
        } catch (InvalidCredentialsException | ValidationException | AccessDeniedException) {
            return $this->errorResponseFactory->unauthenticated($request, 'Invalid bearer token.');
        }

        $request = $request
            ->withAttribute(RequestAttributes::USER_ID, $authResult->userId)
            ->withAttribute(RequestAttributes::AUTH_CHANNEL, 'api')
            ->withAttribute(RequestAttributes::API_TOKEN, $token)
            ->withAttribute(RequestAttributes::AUTH_RESULT, $authResult);

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
}
