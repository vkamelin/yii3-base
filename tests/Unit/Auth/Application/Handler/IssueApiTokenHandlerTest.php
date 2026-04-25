<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application\Handler;

use App\Auth\Application\Command\IssueApiTokenCommand;
use App\Auth\Application\Handler\IssueApiTokenHandler;
use App\Auth\Domain\Entity\AuthToken;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Domain\ValueObject\TokenHash;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use Codeception\Test\Unit;
use DateTimeImmutable;

use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertSame;

final class IssueApiTokenHandlerTest extends Unit
{
    public function testIssueApiToken(): void
    {
        $user = User::create(
            UserId::new(),
            Email::fromString('api@example.com'),
            UserName::fromString('API User'),
            new DateTimeImmutable(),
        );

        $repository = new InMemoryTokenRepository();
        $handler = new IssueApiTokenHandler(
            users: new InMemoryTokenUserRepository($user),
            tokens: $repository,
            tokenGenerator: new FixedTokenGenerator(),
        );

        $result = $handler->handle(new IssueApiTokenCommand(
            userId: $user->id()->value(),
            name: 'My token',
            abilities: ['users.read'],
        ));

        assertNotEmpty($result->tokenId);
        assertSame('plain-token-value', $result->plainToken);
        assertSame('api', $result->tokenType);
        assertSame(1, $repository->count());
    }
}

final class InMemoryTokenUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function save(User $user): void
    {
        $this->user = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->user->id()->equals($id) ? $this->user : null;
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->user->email()->equals($email) ? $this->user : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->user->email()->equals($email);
    }
}

final class InMemoryTokenRepository implements AuthTokenRepositoryInterface
{
    /** @var array<string, AuthToken> */
    private array $tokens = [];

    public function save(AuthToken $token): void
    {
        $this->tokens[$token->id()] = $token;
    }

    public function findByHash(TokenHash $hash): ?AuthToken
    {
        foreach ($this->tokens as $token) {
            if ($token->tokenHash()->value() === $hash->value()) {
                return $token;
            }
        }

        return null;
    }

    public function revokeByHash(TokenHash $hash): void
    {
        foreach ($this->tokens as $token) {
            if ($token->tokenHash()->value() === $hash->value()) {
                $token->revoke(new DateTimeImmutable());
            }
        }
    }

    public function revokeAllForUser(UserId $userId): void
    {
        foreach ($this->tokens as $token) {
            if ($token->userId()->equals($userId)) {
                $token->revoke(new DateTimeImmutable());
            }
        }
    }

    public function count(): int
    {
        return count($this->tokens);
    }
}

final class FixedTokenGenerator implements TokenGeneratorInterface
{
    public function generate(int $bytes = 32): string
    {
        return 'plain-token-value';
    }
}
