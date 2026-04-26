<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit;

interface ActivityLoggerInterface
{
    public function log(ActivityLogEntry $entry): void;
}
