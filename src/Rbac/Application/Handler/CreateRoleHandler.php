<?php

declare(strict_types=1);

namespace App\Rbac\Application\Handler;

use App\Rbac\Application\Command\CreateRoleCommand;
use App\Rbac\Application\DTO\RoleView;
use App\Rbac\Domain\Entity\Role;
use App\Rbac\Domain\Repository\RoleRepositoryInterface;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Rbac\Domain\ValueObject\RoleId;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use DateTimeImmutable;
use Throwable;

final readonly class CreateRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {}

    public function __invoke(CreateRoleCommand $command): RoleView
    {
        return $this->handle($command);
    }

    public function handle(CreateRoleCommand $command): RoleView
    {
        try {
            $code = RoleCode::fromString($command->code);
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        if ($this->roles->existsByCode($code)) {
            throw new ConflictException('Role with this code already exists.');
        }

        $now = new DateTimeImmutable();
        $role = Role::create(
            id: RoleId::new(),
            code: $code,
            name: $command->name,
            description: $command->description,
            isSystem: $command->isSystem,
            now: $now,
        );

        $this->roles->save($role);

        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: ActorContext::ACTOR_ADMIN,
        );
        $this->activityLogger->log(ActivityLogEntry::admin(
            action: AdminAuditAction::ROLE_CREATED,
            actorUserId: $context->userId,
            entityType: 'role',
            entityId: $role->id()->value(),
            payload: [
                'code' => $role->code()->value(),
                'name' => $role->name(),
                'is_system' => $role->isSystem(),
            ],
            context: $context,
        ));

        return new RoleView(
            id: $role->id()->value(),
            code: $role->code()->value(),
            name: $role->name(),
            description: $role->description(),
            isSystem: $role->isSystem(),
            createdAt: $role->createdAt()->format('Y-m-d H:i:s.u'),
            updatedAt: $role->updatedAt()->format('Y-m-d H:i:s.u'),
        );
    }
}
