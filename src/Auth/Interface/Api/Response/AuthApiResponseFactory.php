<?php

declare(strict_types=1);

namespace App\Auth\Interface\Api\Response;

use App\Auth\Application\DTO\ApiTokenResult;
use App\Auth\Application\DTO\AuthResult;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class AuthApiResponseFactory
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function loginSuccess(AuthResult $user, ApiTokenResult $token): ResponseInterface
    {
        return $this->json(200, [
            'user' => [
                'id' => $user->userId,
                'email' => $user->email,
                'name' => $user->name,
                'status' => $user->status,
            ],
            'token' => [
                'type' => 'Bearer',
                'value' => $token->plainToken,
                'expires_at' => $token->expiresAt,
            ],
        ]);
    }

    public function meSuccess(AuthResult $user): ResponseInterface
    {
        return $this->json(200, [
            'user' => [
                'id' => $user->userId,
                'email' => $user->email,
                'name' => $user->name,
                'status' => $user->status,
            ],
        ]);
    }

    public function noContent(): ResponseInterface
    {
        return $this->responseFactory->createResponse(204);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function json(int $statusCode, array $payload): ResponseInterface
    {
        $body = $this->streamFactory->createStream(json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withBody($body);
    }
}
