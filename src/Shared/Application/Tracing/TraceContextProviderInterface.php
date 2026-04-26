<?php

declare(strict_types=1);

namespace App\Shared\Application\Tracing;

interface TraceContextProviderInterface
{
    public function get(): TraceContextInterface;
}
