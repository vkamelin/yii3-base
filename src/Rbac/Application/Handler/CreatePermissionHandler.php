<?php

declare(strict_types=1);

namespace App\Rbac\Application\Handler;

use App\Rbac\Application\Command\CreatePermissionCommand;
use App\Rbac\Application\DTO\PermissionView;
use App\Rbac\Domain\Entity\Permission;
use App\Rbac\Domain\Repository\PermissionRepositoryInterface;
use App\Rbac\Domain\ValueObject\PermissionCode;
use App\Rbac\Domain\ValueObject\PermissionId;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use DateTimeImmutable;
use Throwable;

final readonly class CreatePermissionHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissions,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(CreatePermissionCommand $command): PermissionView
    {
        return $this->handle($command);
    }

    public function handle(CreatePermissionCommand $command): PermissionView
    {
        try {
            $code = PermissionCode::fromString($command->code);
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        if ($this->permissions->existsByCode($code)) {
            throw new ConflictException('Permission with this code already exists.');
        }

        $now = new DateTimeImmutable();
        $permission = Permission::create(
            id: PermissionId::new(),
            code: $code,
            name: $command->name,
            groupCode: $command->groupCode,
            description: $command->description,
            isSystem: $command->isSystem,
            now: $now,
        );

        $this->permissions->save($permission);

        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: ActorContext::ACTOR_ADMIN,
        );
        $this->activityLogger->log(ActivityLogEntry::admin(
            action: AdminAuditAction::PERMISSION_CREATED,
            actorUserId: $context->userId,
            entityType: 'permission',
            entityId: $permission->id()->value(),
            payload: [
                'code' => $permission->code()->value(),
                'name' => $permission->name(),
                'group_code' => $permission->groupCode(),
                'is_system' => $permission->isSystem(),
            ],
            context: $context,
        ));

        return new PermissionView(
            id: $permission->id()->value(),
            code: $permission->code()->value(),
            name: $permission->name(),
            description: $permission->description(),
            groupCode: $permission->groupCode(),
            isSystem: $permission->isSystem(),
            createdAt: $permission->createdAt()->format('Y-m-d H:i:s.u'),
            updatedAt: $permission->updatedAt()->format('Y-m-d H:i:s.u'),
        );
    }
}
