<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Action;

final class DashboardAuditAction
{
    public const LOGIN_SUCCESS = 'dashboard.login.success';
    public const LOGIN_FAILED = 'dashboard.login.failed';
    public const ACCESS_DENIED = 'dashboard.access.denied';

    private function __construct()
    {
    }
}
