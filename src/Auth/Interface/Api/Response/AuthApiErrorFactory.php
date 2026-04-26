<?php

declare(strict_types=1);

namespace App\Auth\Interface\Api\Response;

use Psr\Http\Message\ResponseInterface;

final readonly class AuthApiErrorFactory
{
    public function __construct(
        private AuthApiResponseFactory $responseFactory,
    ) {}

    public function invalidCredentials(): ResponseInterface
    {
        return $this->error(401, 'INVALID_CREDENTIALS', 'Invalid credentials.');
    }

    public function unauthorized(string $code = 'UNAUTHORIZED', string $message = 'Unauthorized.'): ResponseInterface
    {
        return $this->error(401, $code, $message);
    }

    public function forbidden(string $message = 'Access denied.'): ResponseInterface
    {
        return $this->error(403, 'ACCESS_DENIED', $message);
    }

    /**
     * @param array<string, mixed> $details
     */
    public function validation(array $details): ResponseInterface
    {
        return $this->error(422, 'VALIDATION_FAILED', 'Validation failed.', $details);
    }

    public function invalidJson(): ResponseInterface
    {
        return $this->error(422, 'INVALID_JSON', 'Invalid JSON body.');
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public function error(int $statusCode, string $code, string $message, ?array $details = null): ResponseInterface
    {
        return $this->responseFactory->json($statusCode, [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ]);
    }
}
