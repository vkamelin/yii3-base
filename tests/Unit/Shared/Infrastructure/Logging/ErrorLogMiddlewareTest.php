<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Logging;

use App\Shared\Application\Tracing\TraceContext;
use App\Shared\Application\Tracing\TraceContextInterface;
use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Infrastructure\Logging\ErrorLogMiddleware;
use App\Shared\Infrastructure\Logging\LogContext;
use App\Shared\Infrastructure\Logging\LogContextSanitizer;
use Codeception\Test\Unit;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Yiisoft\Router\CurrentRoute;

use function PHPUnit\Framework\assertSame;

final class ErrorLogMiddlewareTest extends Unit
{
    public function testLogsExceptionWithRequestId(): void
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

        $middleware = new ErrorLogMiddleware(
            logger: $logger,
            logContext: new LogContext($traceProvider, new LogContextSanitizer(), new CurrentRoute()),
        );

        $request = new ServerRequest(method: 'POST', uri: '/api/v1/users');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Boom');
            }
        };

        try {
            $middleware->process($request, $handler);
        } catch (RuntimeException) {
        }

        $record = $logger->records[0]['context'];
        assertSame('error', $record['type']);
        assertSame('req-12345678', $record['request_id']);
        assertSame('RuntimeException', $record['exception']);
    }
}
