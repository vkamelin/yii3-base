<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Interface\Http\Middleware;

use App\Shared\Interface\Http\ApiErrorResponder;
use App\Shared\Interface\Http\Middleware\ApiErrorMiddleware;
use App\Shared\Interface\Http\RequestAttributes;
use Codeception\Test\Unit;
use HttpSoft\Message\Response;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function PHPUnit\Framework\assertSame;

final class ApiErrorMiddlewareTest extends Unit
{
    public function testErrorResponseContainsRequestId(): void
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();
        $middleware = new ApiErrorMiddleware(
            errorResponder: new ApiErrorResponder($responseFactory, $streamFactory),
            streamFactory: $streamFactory,
        );

        $request = (new ServerRequest(uri: '/api/v1/auth/me'))
            ->withAttribute(RequestAttributes::REQUEST_ID, 'req-123');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response(401);
                $response = $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
                $response->getBody()->write(
                    '{"error":{"code":"UNAUTHORIZED","message":"Unauthorized.","details":null}}',
                );

                return $response;
            }
        };

        $response = $middleware->process($request, $handler);
        $response->getBody()->rewind();
        $payload = (string) $response->getBody()->getContents();

        assertSame(401, $response->getStatusCode());
        assertSame(
            '{"error":{"code":"UNAUTHORIZED","message":"Unauthorized.","details":null},"request_id":"req-123"}',
            $payload,
        );
    }
}
