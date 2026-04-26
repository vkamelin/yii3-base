<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Tracing;

use App\Shared\Application\Tracing\TraceContext;
use App\Shared\Application\Tracing\TraceContextInterface;
use App\Shared\Application\Tracing\TraceContextProviderInterface;

final class ConsoleTraceContextProvider implements TraceContextProviderInterface
{
    private ?TraceContextInterface $traceContext = null;

    public function __construct(
        private readonly TraceIdGenerator $traceIdGenerator,
    ) {}

    public function get(): TraceContextInterface
    {
        if ($this->traceContext === null) {
            $requestId = $this->traceIdGenerator->generate()->toString();
            $this->traceContext = new TraceContext(
                requestId: $requestId,
                correlationId: $requestId,
                userId: null,
                source: TraceContext::SOURCE_CONSOLE,
            );
        }

        return $this->traceContext;
    }
}
