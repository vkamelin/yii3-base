<?php

declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\LoginCommand;
use App\Auth\Application\DTO\AuthResult;
use App\Auth\Domain\Repository\UserCredentialsRepositoryInterface;
use App\Auth\Domain\Service\PasswordHasherInterface;
use App\Auth\Domain\ValueObject\PlainPassword;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\ApiAuditAction;
use App\Shared\Application\Audit\Action\AuthAuditAction;
use App\Shared\Application\Audit\Action\DashboardAuditAction;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use Throwable;

final readonly class LoginHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserCredentialsRepositoryInterface $credentials,
        private PasswordHasherInterface $passwordHasher,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(LoginCommand $command): AuthResult
    {
        return $this->handle($command);
    }

    public function handle(LoginCommand $command): AuthResult
    {
        try {
            $email = Email::fromString($command->email);
            $password = PlainPassword::fromString($command->password);
        } catch (Throwable $e) {
            $this->logFailure($command->email, 'validation_failed');
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            $this->logFailure($email->value(), 'user_not_found');
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if (!$user->status()->isActive()) {
            $this->logFailure($email->value(), 'user_not_active', $user->id()->value());
            throw new AccessDeniedException('User is not active.');
        }

        $passwordHash = $this->credentials->findPasswordHashByUserId($user->id());
        if ($passwordHash === null) {
            $this->logFailure($email->value(), 'password_hash_missing', $user->id()->value());
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if (!$this->passwordHasher->verify($password, $passwordHash)) {
            $this->logFailure($email->value(), 'password_mismatch', $user->id()->value());
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if ($this->passwordHasher->needsRehash($passwordHash)) {
            $this->credentials->savePasswordHash($user->id(), $this->passwordHasher->hash($password));
        }

        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: ActorContext::ACTOR_USER,
            fallbackUserId: $user->id()->value(),
        );
        $this->activityLogger->log(ActivityLogEntry::user(
            action: $this->successAction(),
            actorUserId: $user->id()->value(),
            entityType: 'user',
            entityId: $user->id()->value(),
            payload: [
                'email' => $user->email()->value(),
            ],
            context: $context,
        ));

        return new AuthResult(
            userId: $user->id()->value(),
            email: $user->email()->value(),
            name: $user->name()->value(),
            status: $user->status()->value,
        );
    }

    private function successAction(): string
    {
        if ($this->auditContext->isApiRequest()) {
            return ApiAuditAction::AUTH_LOGIN_SUCCESS;
        }

        if ($this->auditContext->isDashboardRequest()) {
            return DashboardAuditAction::LOGIN_SUCCESS;
        }

        return AuthAuditAction::LOGIN_SUCCESS;
    }

    private function failedAction(): string
    {
        if ($this->auditContext->isApiRequest()) {
            return ApiAuditAction::AUTH_LOGIN_FAILED;
        }

        if ($this->auditContext->isDashboardRequest()) {
            return DashboardAuditAction::LOGIN_FAILED;
        }

        return AuthAuditAction::LOGIN_FAILED;
    }

    private function logFailure(string $email, string $reason, ?string $userId = null): void
    {
        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: $userId !== null ? ActorContext::ACTOR_USER : ActorContext::ACTOR_GUEST,
            fallbackUserId: $userId,
        );

        $this->activityLogger->log(ActivityLogEntry::user(
            action: $this->failedAction(),
            actorUserId: $userId,
            entityType: 'user',
            entityId: $userId,
            payload: [
                'email' => $email,
                'reason' => $reason,
            ],
            context: $context,
        ));
    }
}
