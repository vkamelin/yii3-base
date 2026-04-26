<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Logging;

use App\Shared\Application\Tracing\TraceContext;
use App\Shared\Application\Tracing\TraceContextInterface;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Infrastructure\Logging\AccessLogMiddleware;
use App\Shared\Infrastructure\Logging\LogContext;
use App\Shared\Infrastructure\Logging\LogContextSanitizer;
use Codeception\Test\Unit;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;
use Yiisoft\Router\CurrentRoute;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertSame;

final class AccessLogMiddlewareTest extends Unit
{
    public function testWritesAccessLogWithStatusDurationAndPath(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var array<int,array{level:string,message:string,context:array<string,mixed>}> */
            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => (string) $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };

        $traceProvider = new class implements TraceContextProviderInterface {
            public function get(): TraceContextInterface
            {
                return new TraceContext('req-12345678', 'corr-12345678', null, TraceContext::SOURCE_API);
            }
        };

        $middleware = new AccessLogMiddleware(
            logger: $logger,
            logContext: new LogContext($traceProvider, new LogContextSanitizer(), new CurrentRoute()),
        );

        $request = new ServerRequest(method: 'GET', uri: '/api/v1/users');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $middleware->process($request, $handler);

        $record = $logger->records[0]['context'];
        assertSame('access', $record['type']);
        assertSame('/api/v1/users', $record['path']);
        assertSame(200, $record['status']);
        assertArrayHasKey('duration_ms', $record);
    }
}
