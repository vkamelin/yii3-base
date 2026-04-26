<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Application\Handler;

use App\Rbac\Application\Command\AssignRoleCommand;
use App\Rbac\Application\Handler\AssignRoleHandler;
use App\Rbac\Domain\Entity\Role;
use App\Rbac\Domain\Repository\RoleRepositoryInterface;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Rbac\Domain\ValueObject\RoleId;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Yiisoft\RequestProvider\RequestProvider;

use function PHPUnit\Framework\assertSame;

final class AssignRoleHandlerTest extends Unit
{
    public function testAssignRoleIsIdempotent(): void
    {
        $user = User::create(
            UserId::new(),
            Email::fromString('assign@example.com'),
            UserName::fromString('Assign User'),
            new DateTimeImmutable(),
        );

        $role = Role::create(
            id: RoleId::new(),
            code: RoleCode::fromString('manager'),
            name: 'Manager',
            description: null,
            isSystem: false,
            now: new DateTimeImmutable(),
        );

        $users = new InMemoryAssignableUserRepository($user);
        $roles = new InMemoryAssignableRoleRepository($role);
        $handler = new AssignRoleHandler(
            $users,
            $roles,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );

        $command = new AssignRoleCommand($user->id()->value(), 'manager');
        $handler->handle($command);
        $handler->handle($command);

        assertSame(1, $roles->assignedCount());
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $user = User::create(
            UserId::new(),
            Email::fromString('assign@example.com'),
            UserName::fromString('Assign User'),
            new DateTimeImmutable(),
        );

        $users = new InMemoryAssignableUserRepository($user);
        $roles = new InMemoryAssignableRoleRepository(null);
        $handler = new AssignRoleHandler(
            $users,
            $roles,
            $this->createNullActivityLogger(),
            new RequestAuditContext(new RequestProvider()),
        );

        $this->expectException(NotFoundException::class);
        $handler->handle(new AssignRoleCommand($user->id()->value(), 'manager'));
    }

    private function createNullActivityLogger(): ActivityLoggerInterface
    {
        return new class implements ActivityLoggerInterface {
            public function log(ActivityLogEntry $entry): void {}
        };
    }
}

final class InMemoryAssignableUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private User $user,
    ) {}

    public function save(User $user): void
    {
        $this->user = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->user->id()->equals($id) ? $this->user : null;
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->user->email()->equals($email) ? $this->user : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->user->email()->equals($email);
    }
}

final class InMemoryAssignableRoleRepository implements RoleRepositoryInterface
{
    /** @var array<string, true> */
    private array $assignments = [];

    public function __construct(
        private ?Role $role,
    ) {}

    public function save(Role $role): void
    {
        $this->role = $role;
    }

    public function findById(RoleId $id): ?Role
    {
        if ($this->role === null) {
            return null;
        }

        return $this->role->id()->equals($id) ? $this->role : null;
    }

    public function findByCode(RoleCode $code): ?Role
    {
        if ($this->role === null) {
            return null;
        }

        return $this->role->code()->equals($code) ? $this->role : null;
    }

    public function existsByCode(RoleCode $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    public function assignToUser(UserId $userId, RoleId $roleId): void
    {
        $this->assignments[$userId->value() . ':' . $roleId->value()] = true;
    }

    public function revokeFromUser(UserId $userId, RoleId $roleId): void
    {
        unset($this->assignments[$userId->value() . ':' . $roleId->value()]);
    }

    public function findByUserId(UserId $userId): array
    {
        if ($this->role === null) {
            return [];
        }

        foreach ($this->assignments as $key => $_) {
            if ($key === $userId->value() . ':' . $this->role->id()->value()) {
                return [$this->role];
            }
        }

        return [];
    }

    public function assignedCount(): int
    {
        return count($this->assignments);
    }
}
