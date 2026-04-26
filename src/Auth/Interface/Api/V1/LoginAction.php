<?php

declare(strict_types=1);

namespace App\Auth\Interface\Api\V1;

use App\Auth\Application\Command\IssueApiTokenCommand;
use App\Auth\Application\Command\LoginCommand;
use App\Auth\Application\Handler\IssueApiTokenHandler;
use App\Auth\Application\Handler\LoginHandler;
use App\Auth\Interface\Api\Request\LoginRequest;
use App\Auth\Interface\Api\Response\AuthApiErrorFactory;
use App\Auth\Interface\Api\Response\AuthApiResponseFactory;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_array;
use function json_decode;

final readonly class LoginAction
{
    public function __construct(
        private LoginHandler $loginHandler,
        private IssueApiTokenHandler $issueApiTokenHandler,
        private AuthApiResponseFactory $responseFactory,
        private AuthApiErrorFactory $errorFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $this->decodeJsonPayload($request);
        if ($payload === null) {
            return $this->errorFactory->invalidJson();
        }

        $loginRequest = LoginRequest::fromArray($payload);
        if (!$loginRequest->isValid()) {
            return $this->errorFactory->validation($loginRequest->errors());
        }

        try {
            $authResult = $this->loginHandler->handle(new LoginCommand(
                email: $loginRequest->email(),
                password: $loginRequest->password(),
                remember: false,
            ));
        } catch (InvalidCredentialsException) {
            return $this->errorFactory->invalidCredentials();
        } catch (AccessDeniedException $e) {
            return $this->errorFactory->forbidden($e->getMessage());
        } catch (ValidationException) {
            return $this->errorFactory->validation(['request' => ['Invalid login request.']]);
        }

        try {
            $tokenResult = $this->issueApiTokenHandler->handle(new IssueApiTokenCommand(
                userId: $authResult->userId,
                name: $loginRequest->tokenName() ?? 'Default API token',
            ));
        } catch (ValidationException) {
            return $this->errorFactory->validation(['token_name' => ['Invalid token name.']]);
        }

        return $this->responseFactory->loginSuccess($authResult, $tokenResult);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function decodeJsonPayload(ServerRequestInterface $request): ?array
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && $parsedBody !== []) {
            /** @var array<array-key, mixed> $parsedBody */
            return $parsedBody;
        }

        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $raw = $body->getContents();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
