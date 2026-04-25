<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Interface\Http\Middleware;

use App\Shared\Interface\Http\Middleware\RequestIdMiddleware;
use App\Shared\Interface\Http\RequestAttributes;
use Codeception\Test\Unit;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;

final class RequestIdMiddlewareTest extends Unit
{
    public function testAddsRequestIdAttributeAndHeader(): void
    {
        $middleware = new RequestIdMiddleware();
        $request = new ServerRequest(uri: '/api/v1/auth/me');

        $state = new stdClass();
        $state->requestId = null;

        $handler = new class ($state) implements RequestHandlerInterface {
            public function __construct(
                private stdClass $state,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->state->requestId = (string) $request->getAttribute(RequestAttributes::REQUEST_ID);
                return new Response(200);
            }
        };

        $response = $middleware->process($request, $handler);

        assertNotSame('', $state->requestId);
        assertSame($state->requestId, $response->getHeaderLine('X-Request-Id'));
    }
}
