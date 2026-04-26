<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Action;

final class AuthAuditAction
{
    public const LOGIN_SUCCESS = 'auth.login.success';
    public const LOGIN_FAILED = 'auth.login.failed';
    public const LOGOUT = 'auth.logout';
    public const API_TOKEN_ISSUED = 'auth.api_token.issued';
    public const API_TOKEN_REVOKED = 'auth.api_token.revoked';
    public const PASSWORD_CHANGED = 'auth.password.changed';
    public const PASSWORD_RESET_REQUESTED = 'auth.password.reset_requested';
    public const PASSWORD_RESET_COMPLETED = 'auth.password.reset_completed';

    private function __construct() {}
}
