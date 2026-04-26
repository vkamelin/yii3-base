<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\Auth;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\Auth\Application\Command\IssueApiTokenCommand;
use App\Auth\Application\Command\LoginCommand;
use App\Auth\Application\Handler\IssueApiTokenHandler;
use App\Auth\Application\Handler\LoginHandler;
use App\Auth\Interface\Api\Request\LoginRequest;
use App\Shared\Application\Exception\InvalidCredentialsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_array;

final readonly class LoginAction
{
    public function __construct(
        private LoginHandler $loginHandler,
        private IssueApiTokenHandler $issueApiTokenHandler,
        private ApiResponseFactory $responseFactory,
        private ApiErrorResponseFactory $errorResponseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $request->getParsedBody();
        if (!is_array($payload)) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', [
                'body' => ['Request body must be a JSON object.'],
            ]);
        }

        $loginRequest = LoginRequest::fromArray($payload);
        if (!$loginRequest->isValid()) {
            return $this->errorResponseFactory->validation($request, 'Validation failed.', $loginRequest->errors());
        }

        try {
            $authResult = $this->loginHandler->handle(new LoginCommand(
                email: $loginRequest->email(),
                password: $loginRequest->password(),
                remember: false,
            ));
        } catch (InvalidCredentialsException) {
            return $this->errorResponseFactory->unauthenticated($request, 'Invalid credentials.');
        }

        $tokenResult = $this->issueApiTokenHandler->handle(new IssueApiTokenCommand(
            userId: $authResult->userId,
            name: $loginRequest->tokenName() ?? 'Default API token',
        ));

        return $this->responseFactory->success($request, [
            'user' => [
                'id' => $authResult->userId,
                'email' => $authResult->email,
                'name' => $authResult->name,
                'status' => $authResult->status,
            ],
            'token' => [
                'type' => 'Bearer',
                'value' => $tokenResult->plainToken,
                'expires_at' => $tokenResult->expiresAt,
            ],
        ]);
    }
}
