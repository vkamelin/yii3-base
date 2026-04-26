<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Audit\Action\UserAuditAction;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\DTO\UserView;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {}

    public function __invoke(UpdateUserCommand $command): UserView
    {
        return $this->handle($command);
    }

    public function handle(UpdateUserCommand $command): UserView
    {
        $id = $command->id();
        $email = $command->email();
        $name = $command->name();

        $user = $this->users->findById($id);
        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        $oldEmail = $user->email()->value();
        $oldName = $user->name()->value();

        if (!$user->email()->equals($email) && $this->users->existsByEmail($email)) {
            throw new ConflictException('User with this email already exists.');
        }

        $now = new DateTimeImmutable();
        $user->changeEmail($email, $now);
        $user->rename($name, $now);
        $this->users->save($user);

        $isAdminOperation = $this->auditContext->isDashboardRequest();
        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: $isAdminOperation ? ActorContext::ACTOR_ADMIN : ActorContext::ACTOR_USER,
        );

        $this->activityLogger->log(ActivityLogEntry::user(
            action: $isAdminOperation ? AdminAuditAction::USER_UPDATED : UserAuditAction::PROFILE_UPDATED,
            actorUserId: $context->userId,
            entityType: 'user',
            entityId: $user->id()->value(),
            payload: [
                'email' => $user->email()->value(),
                'changes' => [
                    'email' => [
                        'from' => $oldEmail,
                        'to' => $user->email()->value(),
                    ],
                    'name' => [
                        'from' => $oldName,
                        'to' => $user->name()->value(),
                    ],
                ],
            ],
            context: $context,
        ));

        return UserViewMapper::fromEntity($user);
    }
}
