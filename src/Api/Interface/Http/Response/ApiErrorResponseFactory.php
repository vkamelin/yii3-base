<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\Response;

use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function is_string;
use function json_encode;

final readonly class ApiErrorResponseFactory
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param array<string, mixed>|null $details
     * @param array<string, string> $headers
     */
    public function create(
        ServerRequestInterface $request,
        int $statusCode,
        string $code,
        string $message,
        ?array $details = null,
        array $headers = [],
    ): ResponseInterface {
        $requestId = $this->requestId($request);

        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'request_id' => $requestId,
        ];

        $response = $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withBody($this->streamFactory->createStream((string) json_encode($payload, JSON_UNESCAPED_UNICODE)));

        if ($requestId !== null) {
            $response = $response->withHeader('X-Request-Id', $requestId);
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $details
     */
    public function validation(
        ServerRequestInterface $request,
        string $message = 'Validation failed.',
        array $details = [],
    ): ResponseInterface {
        return $this->create($request, 422, 'VALIDATION_ERROR', $message, $details);
    }

    public function unauthenticated(
        ServerRequestInterface $request,
        string $message = 'Unauthenticated.',
    ): ResponseInterface {
        return $this->create($request, 401, 'UNAUTHENTICATED', $message);
    }

    public function forbidden(ServerRequestInterface $request, string $message = 'Forbidden.'): ResponseInterface
    {
        return $this->create($request, 403, 'FORBIDDEN', $message);
    }

    public function notFound(ServerRequestInterface $request, string $message = 'Resource not found.'): ResponseInterface
    {
        return $this->create($request, 404, 'NOT_FOUND', $message);
    }

    public function conflict(ServerRequestInterface $request, string $message = 'Conflict.'): ResponseInterface
    {
        return $this->create($request, 409, 'CONFLICT', $message);
    }

    public function internal(ServerRequestInterface $request, string $message = 'Internal server error.'): ResponseInterface
    {
        return $this->create($request, 500, 'INTERNAL_ERROR', $message);
    }

    private function requestId(ServerRequestInterface $request): ?string
    {
        $requestId = $request->getAttribute(RequestAttributes::REQUEST_ID);
        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
