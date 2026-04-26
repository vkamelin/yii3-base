<?php

declare(strict_types=1);

use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\Query\ActivityLogQueryInterface;
use App\Shared\Infrastructure\Audit\MySqlActivityLogQuery;
use App\Shared\Infrastructure\Audit\MySqlActivityLogger;
use App\Shared\Infrastructure\Audit\RequestAuditContext;

return [
    ActivityLoggerInterface::class => MySqlActivityLogger::class,
    ActivityLogQueryInterface::class => MySqlActivityLogQuery::class,
    RequestAuditContext::class => RequestAuditContext::class,
];
