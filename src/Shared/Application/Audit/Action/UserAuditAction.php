<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Action;

final class UserAuditAction
{
    public const REGISTERED = 'user.registered';
    public const PROFILE_UPDATED = 'user.profile.updated';
    public const EMAIL_CHANGED = 'user.email.changed';
    public const STATUS_CHANGED = 'user.status.changed';

    private function __construct()
    {
    }
}
