<?php

declare(strict_types=1);

namespace App\Rbac\Domain\Repository;

use App\Rbac\Domain\Entity\Role;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Rbac\Domain\ValueObject\RoleId;
use App\User\Domain\ValueObject\UserId;

interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findById(RoleId $id): ?Role;

    public function findByCode(RoleCode $code): ?Role;

    public function existsByCode(RoleCode $code): bool;

    public function assignToUser(UserId $userId, RoleId $roleId): void;

    public function revokeFromUser(UserId $userId, RoleId $roleId): void;

    /**
     * @return list<Role>
     */
    public function findByUserId(UserId $userId): array;
}
