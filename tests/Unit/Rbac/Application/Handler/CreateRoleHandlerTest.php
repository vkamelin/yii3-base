<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Application\Handler;

use App\Rbac\Application\Command\CreateRoleCommand;
use App\Rbac\Application\Handler\CreateRoleHandler;
use App\Rbac\Domain\Entity\Role;
use App\Rbac\Domain\Repository\RoleRepositoryInterface;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Rbac\Domain\ValueObject\RoleId;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\ValueObject\UserId;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Yiisoft\RequestProvider\RequestProvider;

use function PHPUnit\Framework\assertSame;

final class CreateRoleHandlerTest extends Unit
{
    public function testCreateRole(): void
    {
        $repository = new InMemoryRoleRepository();
        $handler = new CreateRoleHandler(
            $repository,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );

        $result = $handler->handle(new CreateRoleCommand(
            code: 'auditor',
            name: 'Auditor',
            description: 'Read-only auditing role',
        ));

        assertSame('auditor', $result->code);
        assertSame('Auditor', $result->name);
        assertSame(1, $repository->count());
    }

    public function testDuplicateRoleCodeThrowsConflict(): void
    {
        $repository = new InMemoryRoleRepository();
        $handler = new CreateRoleHandler(
            $repository,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );
        $handler->handle(new CreateRoleCommand('auditor', 'Auditor'));

        $this->expectException(ConflictException::class);
        $handler->handle(new CreateRoleCommand('auditor', 'Auditor 2'));
    }

    public function testInvalidRoleCodeThrowsValidationException(): void
    {
        $repository = new InMemoryRoleRepository();
        $handler = new CreateRoleHandler(
            $repository,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );

        $this->expectException(ValidationException::class);
        $handler->handle(new CreateRoleCommand('INVALID', 'Auditor'));
    }

    private function createNullActivityLogger(): ActivityLoggerInterface
    {
        return new class implements ActivityLoggerInterface {
            public function log(ActivityLogEntry $entry): void
            {
            }
        };
    }
}

final class InMemoryRoleRepository implements RoleRepositoryInterface
{
    /** @var array<string, Role> */
    private array $roles = [];

    /** @var array<string, true> */
    private array $userRoleAssignments = [];

    public function save(Role $role): void
    {
        $this->roles[$role->id()->value()] = $role;
    }

    public function findById(RoleId $id): ?Role
    {
        return $this->roles[$id->value()] ?? null;
    }

    public function findByCode(RoleCode $code): ?Role
    {
        foreach ($this->roles as $role) {
            if ($role->code()->equals($code)) {
                return $role;
            }
        }

        return null;
    }

    public function existsByCode(RoleCode $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    public function assignToUser(UserId $userId, RoleId $roleId): void
    {
        $this->userRoleAssignments[$userId->value() . ':' . $roleId->value()] = true;
    }

    public function revokeFromUser(UserId $userId, RoleId $roleId): void
    {
        unset($this->userRoleAssignments[$userId->value() . ':' . $roleId->value()]);
    }

    public function findByUserId(UserId $userId): array
    {
        $result = [];

        foreach ($this->userRoleAssignments as $key => $_) {
            if (!str_starts_with($key, $userId->value() . ':')) {
                continue;
            }

            $roleId = substr($key, strlen($userId->value() . ':'));
            $role = $this->roles[$roleId] ?? null;
            if ($role !== null) {
                $result[] = $role;
            }
        }

        return $result;
    }

    public function count(): int
    {
        return count($this->roles);
    }

    public function assignedCount(): int
    {
        return count($this->userRoleAssignments);
    }
}
