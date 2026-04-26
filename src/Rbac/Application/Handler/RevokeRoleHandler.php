<?php

declare(strict_types=1);

namespace App\Rbac\Application\Handler;

use App\Rbac\Application\Command\RevokeRoleCommand;
use App\Rbac\Domain\Repository\RoleRepositoryInterface;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\ValueObject\UserId;
use Throwable;

final readonly class RevokeRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(RevokeRoleCommand $command): void
    {
        $this->handle($command);
    }

    public function handle(RevokeRoleCommand $command): void
    {
        try {
            $userId = UserId::fromString($command->userId);
            $roleCode = RoleCode::fromString($command->roleCode);
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $role = $this->roles->findByCode($roleCode);
        if ($role === null) {
            throw new NotFoundException('Role not found.');
        }

        // TODO: add invariant check for preventing last admin role revoke if required by business rules.
        $this->roles->revokeFromUser($userId, $role->id());

        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: ActorContext::ACTOR_ADMIN,
        );
        $this->activityLogger->log(ActivityLogEntry::admin(
            action: AdminAuditAction::USER_ROLE_REVOKED,
            actorUserId: $context->userId,
            entityType: 'user',
            entityId: $userId->value(),
            payload: [
                'role' => $role->code()->value(),
            ],
            context: $context,
        ));
    }
}
