<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Application\Handler;

use App\Rbac\Application\Command\CreatePermissionCommand;
use App\Rbac\Application\Handler\CreatePermissionHandler;
use App\Rbac\Domain\Entity\Permission;
use App\Rbac\Domain\Repository\PermissionRepositoryInterface;
use App\Rbac\Domain\ValueObject\PermissionCode;
use App\Rbac\Domain\ValueObject\PermissionId;
use App\Rbac\Domain\ValueObject\RoleId;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\ValueObject\UserId;
use Codeception\Test\Unit;
use Yiisoft\RequestProvider\RequestProvider;

use function PHPUnit\Framework\assertSame;

final class CreatePermissionHandlerTest extends Unit
{
    public function testCreatePermission(): void
    {
        $repository = new InMemoryPermissionRepository();
        $handler = new CreatePermissionHandler(
            $repository,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );

        $result = $handler->handle(new CreatePermissionCommand(
            code: 'users.assign_role',
            name: 'Assign role',
            groupCode: 'users',
            description: 'Assign role to user',
        ));

        assertSame('users.assign_role', $result->code);
        assertSame('users', $result->groupCode);
        assertSame(1, $repository->count());
    }

    public function testDuplicatePermissionCodeThrowsConflict(): void
    {
        $repository = new InMemoryPermissionRepository();
        $handler = new CreatePermissionHandler(
            $repository,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );
        $handler->handle(new CreatePermissionCommand('users.assign_role', 'Assign role', 'users'));

        $this->expectException(ConflictException::class);
        $handler->handle(new CreatePermissionCommand('users.assign_role', 'Assign role again', 'users'));
    }

    public function testInvalidPermissionCodeThrowsValidationException(): void
    {
        $repository = new InMemoryPermissionRepository();
        $handler = new CreatePermissionHandler(
            $repository,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );

        $this->expectException(ValidationException::class);
        $handler->handle(new CreatePermissionCommand('users', 'Invalid', 'users'));
    }

    private function createNullActivityLogger(): ActivityLoggerInterface
    {
        return new class implements ActivityLoggerInterface {
            public function log(ActivityLogEntry $entry): void {}
        };
    }
}

final class InMemoryPermissionRepository implements PermissionRepositoryInterface
{
    /** @var array<string, Permission> */
    private array $permissions = [];

    /** @var array<string, true> */
    private array $rolePermissionAssignments = [];

    public function save(Permission $permission): void
    {
        $this->permissions[$permission->id()->value()] = $permission;
    }

    public function findById(PermissionId $id): ?Permission
    {
        return $this->permissions[$id->value()] ?? null;
    }

    public function findByCode(PermissionCode $code): ?Permission
    {
        foreach ($this->permissions as $permission) {
            if ($permission->code()->equals($code)) {
                return $permission;
            }
        }

        return null;
    }

    public function existsByCode(PermissionCode $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    public function assignToRole(RoleId $roleId, PermissionId $permissionId): void
    {
        $this->rolePermissionAssignments[$roleId->value() . ':' . $permissionId->value()] = true;
    }

    public function revokeFromRole(RoleId $roleId, PermissionId $permissionId): void
    {
        unset($this->rolePermissionAssignments[$roleId->value() . ':' . $permissionId->value()]);
    }

    public function findByUserId(UserId $userId): array
    {
        return [];
    }

    public function count(): int
    {
        return count($this->permissions);
    }
}
