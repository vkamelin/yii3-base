<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Action;

final class AdminAuditAction
{
    public const USER_CREATED = 'admin.user.created';
    public const USER_UPDATED = 'admin.user.updated';
    public const USER_DELETED = 'admin.user.deleted';
    public const USER_STATUS_CHANGED = 'admin.user.status_changed';
    public const USER_PASSWORD_RESET = 'admin.user.password_reset';
    public const USER_ROLE_ASSIGNED = 'admin.user.role_assigned';
    public const USER_ROLE_REVOKED = 'admin.user.role_revoked';

    public const ROLE_CREATED = 'admin.role.created';
    public const ROLE_UPDATED = 'admin.role.updated';
    public const ROLE_DELETED = 'admin.role.deleted';
    public const PERMISSION_CREATED = 'admin.permission.created';
    public const PERMISSION_UPDATED = 'admin.permission.updated';
    public const PERMISSION_DELETED = 'admin.permission.deleted';
    public const ROLE_PERMISSION_ATTACHED = 'admin.role.permission_attached';
    public const ROLE_PERMISSION_DETACHED = 'admin.role.permission_detached';

    public const ACTIVITY_LOG_VIEWED = 'admin.activity_log.viewed';
    public const ACTIVITY_LOG_EXPORTED = 'admin.activity_log.exported';

    private function __construct()
    {
    }
}
