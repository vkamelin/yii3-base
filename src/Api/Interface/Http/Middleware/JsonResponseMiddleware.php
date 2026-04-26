<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\Middleware;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
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
use function trim;

final readonly class JsonResponseMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     */
    public function __construct(
        private ApiErrorResponseFactory $errorResponseFactory,
        private array $apiPrefixes = ['/api', '/api/'],
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiRequest($request)) {
            return $handler->handle($request);
        }

        $acceptHeader = trim($request->getHeaderLine('Accept'));
        if (
            $acceptHeader !== ''
            && !str_contains($acceptHeader, '*/*')
            && !str_contains($acceptHeader, 'application/json')
            && !str_contains($acceptHeader, 'application/*')
        ) {
            return $this->errorResponseFactory->validation(
                $request,
                'Accept header must allow application/json.',
            );
        }

        if ($this->shouldParse($request)) {
            $contentType = $request->getHeaderLine('Content-Type');
            if (!str_contains($contentType, 'application/json')) {
                return $this->errorResponseFactory->validation(
                    $request,
                    'Content-Type must be application/json.',
                );
            }

            $body = $request->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }

            $rawBody = $body->getContents();
            if ($rawBody === '') {
                $request = $request->withParsedBody([]);
            } else {
                try {
                    $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    return $this->errorResponseFactory->validation($request, 'Invalid JSON body.');
                }

                if (!is_array($decoded)) {
                    return $this->errorResponseFactory->validation(
                        $request,
                        'JSON body must be an object or array.',
                    );
                }

                $request = $request->withParsedBody($decoded);
            }
        }

        $response = $handler->handle($request);

        if ($response->getHeaderLine('Content-Type') === '') {
            $response = $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        return $response;
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
        return in_array($request->getMethod(), [Method::POST, Method::PUT, Method::PATCH], true);
    }
}
