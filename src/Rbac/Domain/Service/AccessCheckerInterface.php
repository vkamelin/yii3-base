<?php

declare(strict_types=1);

namespace App\Rbac\Domain\Service;

use App\User\Domain\ValueObject\UserId;

interface AccessCheckerInterface
{
    public function userHasPermission(UserId $userId, string $permissionCode): bool;

    /**
     * @param non-empty-list<string> $permissionCodes
     */
    public function userHasAnyPermission(UserId $userId, array $permissionCodes): bool;

    /**
     * @param non-empty-list<string> $permissionCodes
     */
    public function userHasAllPermissions(UserId $userId, array $permissionCodes): bool;
}
