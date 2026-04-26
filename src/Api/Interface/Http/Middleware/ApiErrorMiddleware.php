<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\Middleware;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function is_array;
use function is_string;
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
        private ApiErrorResponseFactory $errorResponseFactory,
        private StreamFactoryInterface $streamFactory,
        private TraceContextProviderInterface $traceContextProvider,
        private array $apiPrefixes = ['/api', '/api/'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiRequest($request)) {
            return $handler->handle($request);
        }

        try {
            $response = $handler->handle($request);
        } catch (ValidationException $e) {
            return $this->errorResponseFactory->validation($request, $e->getMessage() ?: 'Validation failed.');
        } catch (InvalidCredentialsException $e) {
            return $this->errorResponseFactory->unauthenticated($request, $e->getMessage() ?: 'Unauthenticated.');
        } catch (AccessDeniedException $e) {
            return $this->errorResponseFactory->forbidden($request, $e->getMessage() ?: 'Forbidden.');
        } catch (NotFoundException $e) {
            return $this->errorResponseFactory->notFound($request, $e->getMessage() ?: 'Resource not found.');
        } catch (ConflictException $e) {
            return $this->errorResponseFactory->conflict($request, $e->getMessage() ?: 'Conflict.');
        } catch (Throwable) {
            return $this->errorResponseFactory->internal($request);
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
                    $requestId = $request->getAttribute(RequestAttributes::REQUEST_ID);
                    $decoded['request_id'] = is_string($requestId)
                        ? $requestId
                        : $this->traceContextProvider->get()->requestId();
                }

                $stream = $this->streamFactory->createStream((string) json_encode($decoded, JSON_UNESCAPED_UNICODE));
                return $response
                    ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                    ->withBody($stream);
            }
        }

        return match ($response->getStatusCode()) {
            401 => $this->errorResponseFactory->unauthenticated($request),
            403 => $this->errorResponseFactory->forbidden($request),
            404 => $this->errorResponseFactory->notFound($request),
            409 => $this->errorResponseFactory->conflict($request),
            422 => $this->errorResponseFactory->validation($request),
            default => $this->errorResponseFactory->internal($request),
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
