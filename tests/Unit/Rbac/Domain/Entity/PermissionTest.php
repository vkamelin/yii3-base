<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Domain\Entity;

use App\Rbac\Domain\Entity\Permission;
use App\Rbac\Domain\ValueObject\PermissionCode;
use App\Rbac\Domain\ValueObject\PermissionId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    public function testCreateAndMutatePermission(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00.000000');
        $updatedAt = new DateTimeImmutable('2026-01-01 11:00:00.000000');
        $permission = Permission::create(
            id: PermissionId::new(),
            code: PermissionCode::fromString('users.view'),
            name: 'View users',
            groupCode: 'users',
            description: 'Read only access',
            isSystem: false,
            now: $createdAt,
        );

        $permission->rename('View all users', $updatedAt);
        $permission->changeDescription('Can read users list', $updatedAt);

        self::assertSame('View all users', $permission->name());
        self::assertSame('Can read users list', $permission->description());
        self::assertSame('users', $permission->groupCode());
        self::assertSame('2026-01-01 11:00:00.000000', $permission->updatedAt()->format('Y-m-d H:i:s.u'));
    }
}
