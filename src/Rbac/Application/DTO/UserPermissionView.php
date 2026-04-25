<?php

declare(strict_types=1);

namespace App\Rbac\Application\DTO;

final readonly class UserPermissionView
{
    public function __construct(
        public string $userId,
        public string $roleCode,
        public string $permissionCode,
    ) {
    }
}
