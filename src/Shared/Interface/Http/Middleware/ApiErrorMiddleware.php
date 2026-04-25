<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function is_array;
use function json_decode;
use function json_encode;
use function str_contains;
use function str_starts_with;

final readonly class ApiErrorMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $apiPrefixes
     */
    public function __construct(
        private ApiErrorResponder $errorResponder,
        private StreamFactoryInterface $streamFactory,
        private array $apiPrefixes = ['/api', '/api/'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiRequest($request)) {
            return $handler->handle($request);
        }

        try {
            $response = $handler->handle($request);
        } catch (InvalidCredentialsException $e) {
            return $this->errorResponder->error($request, 401, 'UNAUTHORIZED', $e->getMessage() ?: 'Unauthorized.');
        } catch (AccessDeniedException $e) {
            return $this->errorResponder->error($request, 403, 'FORBIDDEN', $e->getMessage() ?: 'Forbidden.');
        } catch (ValidationException $e) {
            return $this->errorResponder->error($request, 422, 'VALIDATION_FAILED', $e->getMessage() ?: 'Validation failed.');
        } catch (Throwable) {
            return $this->errorResponder->error($request, 500, 'INTERNAL_ERROR', 'Internal server error.');
        }

        if ($response->getStatusCode() < 400) {
            return $response;
        }

        return $this->normalizeErrorResponse($request, $response);
    }

    private function normalizeErrorResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (str_contains($response->getHeaderLine('Content-Type'), 'application/json')) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }

            $raw = $body->getContents();
            $decoded = $raw !== '' ? json_decode($raw, true) : null;

            if (is_array($decoded) && isset($decoded['error']) && is_array($decoded['error'])) {
                if (!isset($decoded['request_id'])) {
                    $decoded['request_id'] = $request->getAttribute(RequestAttributes::REQUEST_ID);
                }

                $stream = $this->streamFactory->createStream((string) json_encode($decoded, JSON_UNESCAPED_UNICODE));
                return $response->withBody($stream);
            }
        }

        $status = $response->getStatusCode();
        [$code, $message] = $this->defaultError($status);

        $headers = [];
        foreach (['Retry-After'] as $headerName) {
            $headerValue = $response->getHeaderLine($headerName);
            if ($headerValue !== '') {
                $headers[$headerName] = $headerValue;
            }
        }

        return $this->errorResponder->error($request, $status, $code, $message, null, $headers);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function defaultError(int $status): array
    {
        return match ($status) {
            401 => ['UNAUTHORIZED', 'Unauthorized.'],
            403 => ['FORBIDDEN', 'Forbidden.'],
            404 => ['NOT_FOUND', 'Resource not found.'],
            409 => ['CONFLICT', 'Conflict.'],
            422 => ['VALIDATION_FAILED', 'Validation failed.'],
            429 => ['RATE_LIMIT_EXCEEDED', 'Rate limit exceeded.'],
            default => ['HTTP_ERROR', 'HTTP request failed.'],
        };
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
}
