<?php

declare(strict_types=1);

namespace App\Auth\Interface\Api\V1;

use App\Auth\Application\Command\GetAuthenticatedUserCommand;
use App\Auth\Application\Handler\GetAuthenticatedUserHandler;
use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Auth\Interface\Api\Response\AuthApiErrorFactory;
use App\Auth\Interface\Api\Response\AuthApiResponseFactory;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MeAction
{
    public function __construct(
        private BearerTokenExtractor $bearerTokenExtractor,
        private GetAuthenticatedUserHandler $getAuthenticatedUserHandler,
        private AuthApiResponseFactory $responseFactory,
        private AuthApiErrorFactory $errorFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->bearerTokenExtractor->extract($request);
        if ($token === null) {
            return $this->errorFactory->unauthorized('UNAUTHORIZED', 'Missing bearer token.');
        }

        try {
            $user = $this->getAuthenticatedUserHandler->handle(new GetAuthenticatedUserCommand($token));
            return $this->responseFactory->meSuccess($user);
        } catch (AccessDeniedException $e) {
            return $this->errorFactory->forbidden($e->getMessage());
        } catch (InvalidCredentialsException|ValidationException) {
            return $this->errorFactory->unauthorized('INVALID_TOKEN', 'Invalid token.');
        }
    }
}
