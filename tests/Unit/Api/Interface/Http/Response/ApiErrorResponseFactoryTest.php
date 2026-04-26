<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Interface\Http\Response;

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Shared\Application\Tracing\TraceContext;
use App\Shared\Application\Tracing\TraceContextInterface;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use Codeception\Test\Unit;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\StreamFactory;

use function is_array;
use function json_decode;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class ApiErrorResponseFactoryTest extends Unit
{
    public function testUnauthenticatedContainsRequestId(): void
    {
        $factory = $this->createFactory();
        $request = new ServerRequest(uri: '/api/v1/auth/me');

        $response = $factory->unauthenticated($request);
        $payload = $this->decodePayload($response->getBody()->getContents());

        assertSame(401, $response->getStatusCode());
        assertSame('req-12345678', $payload['request_id']);
    }

    public function testForbiddenContainsRequestId(): void
    {
        $factory = $this->createFactory();
        $request = new ServerRequest(uri: '/api/v1/users');

        $response = $factory->forbidden($request);
        $payload = $this->decodePayload($response->getBody()->getContents());

        assertSame(403, $response->getStatusCode());
        assertSame('req-12345678', $payload['request_id']);
    }

    public function testRateLimitContainsRequestId(): void
    {
        $factory = $this->createFactory();
        $request = new ServerRequest(uri: '/api/v1/auth/login');

        $response = $factory->create(
            request: $request,
            statusCode: 429,
            code: 'RATE_LIMIT_EXCEEDED',
            message: 'Rate limit exceeded.',
        );
        $payload = $this->decodePayload($response->getBody()->getContents());

        assertSame(429, $response->getStatusCode());
        assertSame('req-12345678', $payload['request_id']);
    }

    private function createFactory(): ApiErrorResponseFactory
    {
        $traceProvider = new class implements TraceContextProviderInterface {
            public function get(): TraceContextInterface
            {
                return new TraceContext('req-12345678', 'corr-12345678', null, TraceContext::SOURCE_API);
            }
        };

        return new ApiErrorResponseFactory(new ResponseFactory(), new StreamFactory(), $traceProvider);
    }

    /**
     * @return array<string,mixed>
     */
    private function decodePayload(string $json): array
    {
        $decoded = json_decode($json, true);
        assertTrue(is_array($decoded));

        return $decoded;
    }
}
