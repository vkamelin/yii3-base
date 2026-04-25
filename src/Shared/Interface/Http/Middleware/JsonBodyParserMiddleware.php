<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use App\Shared\Interface\Http\ApiErrorResponder;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;

use function in_array;
use function is_array;
use function json_decode;
use function str_contains;
use function str_starts_with;

final readonly class JsonBodyParserMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     */
    public function __construct(
        private ApiErrorResponder $errorResponder,
        private array $apiPrefixes = ['/api', '/api/'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiRequest($request) || !$this->shouldParse($request)) {
            return $handler->handle($request);
        }

        if (!str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            return $handler->handle($request);
        }

        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $rawBody = $body->getContents();
        if ($rawBody === '') {
            return $handler->handle($request->withParsedBody([]));
        }

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->errorResponder->error(
                request: $request,
                statusCode: 422,
                code: 'INVALID_JSON',
                message: 'Invalid JSON body.',
            );
        }

        if (!is_array($decoded)) {
            return $this->errorResponder->error(
                request: $request,
                statusCode: 422,
                code: 'INVALID_JSON',
                message: 'JSON body must be an object or array.',
            );
        }

        return $handler->handle($request->withParsedBody($decoded));
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        foreach ($this->apiPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function shouldParse(ServerRequestInterface $request): bool
    {
        return in_array($request->getMethod(), [Method::POST, Method::PUT, Method::PATCH, Method::DELETE], true);
    }
}
