<?php

declare(strict_types=1);

namespace App\Auth\Interface\Api\V1;

use App\Auth\Application\Command\LogoutCommand;
use App\Auth\Application\Handler\LogoutHandler;
use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Auth\Interface\Api\Response\AuthApiErrorFactory;
use App\Auth\Interface\Api\Response\AuthApiResponseFactory;
use App\Shared\Application\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LogoutAction
{
    public function __construct(
        private BearerTokenExtractor $bearerTokenExtractor,
        private LogoutHandler $logoutHandler,
        private AuthApiResponseFactory $responseFactory,
        private AuthApiErrorFactory $errorFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->bearerTokenExtractor->extract($request);
        if ($token === null) {
            return $this->errorFactory->unauthorized('UNAUTHORIZED', 'Missing bearer token.');
        }

        try {
            $this->logoutHandler->handle(new LogoutCommand($token));
        } catch (ValidationException) {
            return $this->errorFactory->unauthorized('INVALID_TOKEN', 'Invalid token.');
        }

        return $this->responseFactory->noContent();
    }
}
