<?php

declare(strict_types=1);

namespace App\Rbac\Application\Handler;

use App\Rbac\Application\Command\AssignRoleCommand;
use App\Rbac\Domain\Repository\RoleRepositoryInterface;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Application\Exception\ValidationException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Throwable;

final readonly class AssignRoleHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {
    }

    public function __invoke(AssignRoleCommand $command): void
    {
        $this->handle($command);
    }

    public function handle(AssignRoleCommand $command): void
    {
        try {
            $userId = UserId::fromString($command->userId);
            $roleCode = RoleCode::fromString($command->roleCode);
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        $role = $this->roles->findByCode($roleCode);
        if ($role === null) {
            throw new NotFoundException('Role not found.');
        }

        $this->roles->assignToUser($userId, $role->id());
    }
}
