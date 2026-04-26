<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Action;

final class QueueAuditAction
{
    public const JOB_DISPATCHED = 'queue.job.dispatched';
    public const JOB_STARTED = 'queue.job.started';
    public const JOB_COMPLETED = 'queue.job.completed';
    public const JOB_FAILED = 'queue.job.failed';
    public const JOB_RETRIED = 'queue.job.retried';

    private function __construct() {}
}
