<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\Auth;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\Auth\Application\Command\LogoutCommand;
use App\Auth\Application\Handler\LogoutHandler;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_string;

final readonly class LogoutAction
{
    public function __construct(
        private LogoutHandler $logoutHandler,
        private ApiResponseFactory $responseFactory,
        private ApiErrorResponseFactory $errorResponseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $token = $request->getAttribute(RequestAttributes::API_TOKEN);
        if (!is_string($token) || $token === '') {
            return $this->errorResponseFactory->unauthenticated($request, 'Missing bearer token.');
        }

        $this->logoutHandler->handle(new LogoutCommand($token));

        return $this->responseFactory->success($request, ['success' => true]);
    }
}
