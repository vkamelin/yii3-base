<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Audit\Action\UserAuditAction;
use App\Shared\Application\Exception\ConflictException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\DTO\UserView;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(CreateUserCommand $command): UserView
    {
        return $this->handle($command);
    }

    public function handle(CreateUserCommand $command): UserView
    {
        $email = $command->email();
        $name = $command->name();

        if ($this->users->existsByEmail($email)) {
            throw new ConflictException('User with this email already exists.');
        }

        $now = new DateTimeImmutable();
        $user = User::create(UserId::new(), $email, $name, $now);
        $this->users->save($user);

        $isAdminOperation = $this->auditContext->isDashboardRequest();
        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: $isAdminOperation ? ActorContext::ACTOR_ADMIN : ActorContext::ACTOR_USER,
        );

        $this->activityLogger->log(ActivityLogEntry::user(
            action: $isAdminOperation ? AdminAuditAction::USER_CREATED : UserAuditAction::REGISTERED,
            actorUserId: $context->userId,
            entityType: 'user',
            entityId: $user->id()->value(),
            payload: [
                'email' => $user->email()->value(),
                'name' => $user->name()->value(),
                'status' => $user->status()->value,
            ],
            context: $context,
        ));

        return UserViewMapper::fromEntity($user);
    }
}
