<?php

declare(strict_types=1);

use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Infrastructure\Tracing\ConsoleTraceContextProvider;

return [
    TraceContextProviderInterface::class => ConsoleTraceContextProvider::class,
];
