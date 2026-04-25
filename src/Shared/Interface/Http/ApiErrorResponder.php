<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function is_string;
use function json_encode;

final readonly class ApiErrorResponder
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @param array<string, mixed>|null $details
     * @param array<string, string> $headers
     */
    public function error(
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

        $body = $this->streamFactory->createStream((string) json_encode($payload, JSON_UNESCAPED_UNICODE));

        $response = $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withBody($body);

        if ($requestId !== null) {
            $response = $response->withHeader('X-Request-Id', $requestId);
        }

        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }

        return $response;
    }

    private function requestId(ServerRequestInterface $request): ?string
    {
        $requestId = $request->getAttribute(RequestAttributes::REQUEST_ID);
        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
