<?php

declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\LogoutCommand;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\TokenHash;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\ApiAuditAction;
use App\Shared\Application\Audit\Action\AuthAuditAction;
use App\Shared\Application\Exception\ValidationException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use Throwable;

final readonly class LogoutHandler
{
    public function __construct(
        private AuthTokenRepositoryInterface $tokens,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(LogoutCommand $command): void
    {
        $this->handle($command);
    }

    public function handle(LogoutCommand $command): void
    {
        if ($command->token === null) {
            return;
        }

        try {
            $hash = TokenHash::fromPlainToken($command->token);
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $this->tokens->revokeByHash($hash);

        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: ActorContext::ACTOR_USER,
        );
        $this->activityLogger->log(ActivityLogEntry::user(
            action: $this->auditContext->isApiRequest() ? ApiAuditAction::AUTH_LOGOUT : AuthAuditAction::LOGOUT,
            actorUserId: $context->userId,
            payload: [
                'token_revoked' => true,
            ],
            context: $context,
        ));
    }
}
