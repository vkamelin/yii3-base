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

final readonly class ApiResponseFactory
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param array<string, mixed>|list<mixed>|null $data
     */
    public function success(ServerRequestInterface $request, array|null $data, int $statusCode = 200): ResponseInterface
    {
        return $this->json($request, $statusCode, [
            'data' => $data,
        ]);
    }

    /**
     * @param list<mixed> $data
     */
    public function paginated(
        ServerRequestInterface $request,
        array $data,
        int $page,
        int $perPage,
        int $total,
        int $statusCode = 200,
    ): ResponseInterface {
        return $this->json($request, $statusCode, [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function json(ServerRequestInterface $request, int $statusCode, array $payload): ResponseInterface
    {
        $requestId = $this->requestId($request);
        $payload['request_id'] = $requestId;

        $response = $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withBody($this->streamFactory->createStream((string) json_encode($payload, JSON_UNESCAPED_UNICODE)));

        if ($requestId !== null) {
            $response = $response->withHeader('X-Request-Id', $requestId);
        }

        return $response;
    }

    private function requestId(ServerRequestInterface $request): ?string
    {
        $requestId = $request->getAttribute(RequestAttributes::REQUEST_ID);
        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
