<?php

declare(strict_types=1);

namespace App\Rbac\Domain\Repository;

use App\Rbac\Domain\Entity\Permission;
use App\Rbac\Domain\ValueObject\PermissionCode;
use App\Rbac\Domain\ValueObject\PermissionId;
use App\Rbac\Domain\ValueObject\RoleId;
use App\User\Domain\ValueObject\UserId;

interface PermissionRepositoryInterface
{
    public function save(Permission $permission): void;

    public function findById(PermissionId $id): ?Permission;

    public function findByCode(PermissionCode $code): ?Permission;

    public function existsByCode(PermissionCode $code): bool;

    public function assignToRole(RoleId $roleId, PermissionId $permissionId): void;

    public function revokeFromRole(RoleId $roleId, PermissionId $permissionId): void;

    /**
     * @return list<Permission>
     */
    public function findByUserId(UserId $userId): array;
}
