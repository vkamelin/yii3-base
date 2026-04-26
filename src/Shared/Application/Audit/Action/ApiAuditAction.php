<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Action;

final class ApiAuditAction
{
    public const AUTH_LOGIN_SUCCESS = 'api.auth.login.success';
    public const AUTH_LOGIN_FAILED = 'api.auth.login.failed';
    public const AUTH_LOGOUT = 'api.auth.logout';
    public const TOKEN_AUTH_FAILED = 'api.token.auth.failed';
    public const RATE_LIMIT_EXCEEDED = 'api.rate_limit.exceeded';

    private function __construct() {}
}
