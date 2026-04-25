<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Domain\Entity;

use App\Rbac\Domain\Entity\Role;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Rbac\Domain\ValueObject\RoleId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function testCreateAndMutateRole(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 10:00:00.000000');
        $updatedAt = new DateTimeImmutable('2026-01-01 11:00:00.000000');
        $role = Role::create(
            id: RoleId::new(),
            code: RoleCode::fromString('manager'),
            name: 'Manager',
            description: 'Can view users',
            isSystem: false,
            now: $createdAt,
        );

        $role->rename('Operations Manager', $updatedAt);
        $role->changeDescription('Can manage users', $updatedAt);
        $role->markAsSystem($updatedAt);

        self::assertSame('Operations Manager', $role->name());
        self::assertSame('Can manage users', $role->description());
        self::assertTrue($role->isSystem());
        self::assertSame('2026-01-01 11:00:00.000000', $role->updatedAt()->format('Y-m-d H:i:s.u'));
    }
}
