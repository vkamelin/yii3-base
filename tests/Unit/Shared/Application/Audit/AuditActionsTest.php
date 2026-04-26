<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Audit;

use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Audit\Action\ApiAuditAction;
use App\Shared\Application\Audit\Action\AuthAuditAction;
use App\Shared\Application\Audit\Action\DashboardAuditAction;
use App\Shared\Application\Audit\Action\QueueAuditAction;
use App\Shared\Application\Audit\Action\SystemAuditAction;
use App\Shared\Application\Audit\Action\UserAuditAction;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertSame;

final class AuditActionsTest extends Unit
{
    public function testContainsExpectedEvents(): void
    {
        assertSame('auth.login.success', AuthAuditAction::LOGIN_SUCCESS);
        assertSame('user.registered', UserAuditAction::REGISTERED);
        assertSame('api.token.auth.failed', ApiAuditAction::TOKEN_AUTH_FAILED);
        assertSame('queue.job.failed', QueueAuditAction::JOB_FAILED);
        assertSame('dashboard.access.denied', DashboardAuditAction::ACCESS_DENIED);
        assertSame('admin.user.created', AdminAuditAction::USER_CREATED);
        assertSame('system.seed.executed', SystemAuditAction::SEED_EXECUTED);
    }
}
