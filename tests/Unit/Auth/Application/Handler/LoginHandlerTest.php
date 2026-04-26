<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application\Handler;

use App\Auth\Application\Command\LoginCommand;
use App\Auth\Application\Handler\LoginHandler;
use App\Auth\Domain\Repository\UserCredentialsRepositoryInterface;
use App\Auth\Domain\Service\PasswordHasherInterface;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\PlainPassword;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Yiisoft\RequestProvider\RequestProvider;

use function PHPUnit\Framework\assertSame;

final class LoginHandlerTest extends Unit
{
    public function testLoginSuccess(): void
    {
        $user = User::create(
            UserId::new(),
            Email::fromString('user@example.com'),
            UserName::fromString('Demo User'),
            new DateTimeImmutable(),
        );
        $passwordHash = PasswordHash::fromString(password_hash('strong-pass-123', PASSWORD_BCRYPT));

        $handler = new LoginHandler(
            users: new InMemoryUserRepository($user),
            credentials: new InMemoryCredentialsRepository($user->id(), $passwordHash),
            passwordHasher: new NativePasswordHasher(),
            activityLogger: $this->createNullActivityLogger(),
            auditContext: new RequestAuditContext(new RequestProvider()),
        );

        $result = $handler->handle(new LoginCommand('user@example.com', 'strong-pass-123'));
        assertSame($user->id()->value(), $result->userId);
        assertSame('user@example.com', $result->email);
    }

    public function testLoginWithInvalidPasswordThrows(): void
    {
        $user = User::create(
            UserId::new(),
            Email::fromString('user@example.com'),
            UserName::fromString('Demo User'),
            new DateTimeImmutable(),
        );
        $passwordHash = PasswordHash::fromString(password_hash('strong-pass-123', PASSWORD_BCRYPT));

        $handler = new LoginHandler(
            users: new InMemoryUserRepository($user),
            credentials: new InMemoryCredentialsRepository($user->id(), $passwordHash),
            passwordHasher: new NativePasswordHasher(),
            activityLogger: $this->createNullActivityLogger(),
            auditContext: new RequestAuditContext(new RequestProvider()),
        );

        $this->expectException(InvalidCredentialsException::class);
        $handler->handle(new LoginCommand('user@example.com', 'wrong-pass-123'));
    }

    private function createNullActivityLogger(): ActivityLoggerInterface
    {
        return new class implements ActivityLoggerInterface {
            public function log(ActivityLogEntry $entry): void
            {
            }
        };
    }
}

final class InMemoryUserRepository implements UserRepositoryInterface
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

final class InMemoryCredentialsRepository implements UserCredentialsRepositoryInterface
{
    public function __construct(
        private UserId $userId,
        private PasswordHash $passwordHash,
    ) {
    }

    public function findPasswordHashByUserId(UserId $userId): ?PasswordHash
    {
        return $this->userId->equals($userId) ? $this->passwordHash : null;
    }

    public function savePasswordHash(UserId $userId, PasswordHash $hash): void
    {
        if ($this->userId->equals($userId)) {
            $this->passwordHash = $hash;
        }
    }
}

final class NativePasswordHasher implements PasswordHasherInterface
{
    public function hash(PlainPassword $password): PasswordHash
    {
        return PasswordHash::fromString(password_hash($password->value(), PASSWORD_BCRYPT));
    }

    public function verify(PlainPassword $password, PasswordHash $hash): bool
    {
        return password_verify($password->value(), $hash->value());
    }

    public function needsRehash(PasswordHash $hash): bool
    {
        return password_needs_rehash($hash->value(), PASSWORD_BCRYPT);
    }
}
