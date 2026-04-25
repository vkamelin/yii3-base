<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use App\Shared\Interface\Http\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

use function trim;

final class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $incomingRequestId = trim($request->getHeaderLine('X-Request-Id'));
        $requestId = $incomingRequestId !== '' ? $incomingRequestId : Uuid::uuid4()->toString();

        $request = $request->withAttribute(RequestAttributes::REQUEST_ID, $requestId);
        $response = $handler->handle($request);

        if (!$response->hasHeader('X-Request-Id')) {
            $response = $response->withHeader('X-Request-Id', $requestId);
        }

        return $response;
    }
}
