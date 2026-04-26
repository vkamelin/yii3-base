<?php

declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\IssueApiTokenCommand;
use App\Auth\Application\DTO\ApiTokenResult;
use App\Auth\Domain\Entity\AuthToken;
use App\Auth\Domain\Enum\AuthTokenType;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\TokenHash;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AuthAuditAction;
use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final readonly class IssueApiTokenHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AuthTokenRepositoryInterface $tokens,
        private TokenGeneratorInterface $tokenGenerator,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(IssueApiTokenCommand $command): ApiTokenResult
    {
        return $this->handle($command);
    }

    public function handle(IssueApiTokenCommand $command): ApiTokenResult
    {
        $userId = UserId::fromString($command->userId);
        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        $now = new DateTimeImmutable();
        $plainToken = $this->tokenGenerator->generate();
        $token = AuthToken::issue(
            id: Uuid::uuid7()->toString(),
            userId: $userId,
            tokenHash: TokenHash::fromPlainToken($plainToken),
            type: AuthTokenType::Api,
            name: $command->name,
            abilities: $command->abilities,
            expiresAt: $command->expiresAt,
            now: $now,
        );

        $this->tokens->save($token);

        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_API,
            defaultActorType: ActorContext::ACTOR_USER,
            fallbackUserId: $userId->value(),
        );
        $this->activityLogger->log(ActivityLogEntry::api(
            action: AuthAuditAction::API_TOKEN_ISSUED,
            actorUserId: $userId->value(),
            entityType: 'auth_token',
            entityId: $token->id(),
            payload: [
                'token_type' => $token->type()->value,
                'name' => $token->name(),
                'expires_at' => $token->expiresAt()?->format(DATE_ATOM),
            ],
            context: $context,
        ));

        return new ApiTokenResult(
            tokenId: $token->id(),
            plainToken: $plainToken,
            tokenType: $token->type()->value,
            expiresAt: $token->expiresAt()?->format(DATE_ATOM),
        );
    }
}
