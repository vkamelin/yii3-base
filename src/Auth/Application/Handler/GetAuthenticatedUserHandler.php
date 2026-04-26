<?php

declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\GetAuthenticatedUserCommand;
use App\Auth\Application\DTO\AuthResult;
use App\Auth\Domain\Enum\AuthTokenType;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\TokenHash;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Throwable;

final readonly class GetAuthenticatedUserHandler
{
    public function __construct(
        private AuthTokenRepositoryInterface $tokens,
        private UserRepositoryInterface $users,
    ) {}

    public function __invoke(GetAuthenticatedUserCommand $command): AuthResult
    {
        return $this->handle($command);
    }

    public function handle(GetAuthenticatedUserCommand $command): AuthResult
    {
        try {
            $hash = TokenHash::fromPlainToken($command->token);
        } catch (Throwable $e) {
            throw new ValidationException('Invalid token.', 0, $e);
        }

        $token = $this->tokens->findByHash($hash);
        if ($token === null) {
            throw new InvalidCredentialsException('Invalid token.');
        }

        $now = new DateTimeImmutable();
        if ($token->type() !== AuthTokenType::Api || $token->isRevoked() || $token->isExpired($now)) {
            throw new InvalidCredentialsException('Invalid token.');
        }

        $user = $this->users->findById($token->userId());
        if ($user === null) {
            throw new InvalidCredentialsException('Invalid token.');
        }

        if (!$user->status()->isActive()) {
            throw new AccessDeniedException('User is not active.');
        }

        $token->markUsed($now);
        $this->tokens->save($token);

        return new AuthResult(
            userId: $user->id()->value(),
            email: $user->email()->value(),
            name: $user->name()->value(),
            status: $user->status()->value,
        );
    }
}
