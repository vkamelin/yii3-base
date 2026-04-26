<?php

declare(strict_types=1);

use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Infrastructure\Tracing\RequestTraceContextProvider;

return [
    TraceContextProviderInterface::class => RequestTraceContextProvider::class,
];
