<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Audit\Action\UserAuditAction;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Application\Command\ChangeUserStatusCommand;
use App\User\Application\DTO\UserView;
use App\User\Domain\Enum\UserStatus;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Throwable;

final readonly class ChangeUserStatusHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(ChangeUserStatusCommand $command): UserView
    {
        return $this->handle($command);
    }

    public function handle(ChangeUserStatusCommand $command): UserView
    {
        try {
            $id = $command->userId();
            $status = $command->userStatus();
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        $fromStatus = $user->status()->value;
        $now = new DateTimeImmutable();
        match ($status) {
            UserStatus::Active => $user->activate($now),
            UserStatus::Blocked => $user->block($now),
            UserStatus::Pending => $user->markPending($now),
        };

        $this->users->save($user);

        $isAdminOperation = $this->auditContext->isDashboardRequest();
        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: $isAdminOperation ? ActorContext::ACTOR_ADMIN : ActorContext::ACTOR_USER,
        );

        $this->activityLogger->log(ActivityLogEntry::user(
            action: $isAdminOperation ? AdminAuditAction::USER_STATUS_CHANGED : UserAuditAction::STATUS_CHANGED,
            actorUserId: $context->userId,
            entityType: 'user',
            entityId: $user->id()->value(),
            payload: [
                'changes' => [
                    'status' => [
                        'from' => $fromStatus,
                        'to' => $user->status()->value,
                    ],
                ],
            ],
            context: $context,
        ));

        return UserViewMapper::fromEntity($user);
    }
}
