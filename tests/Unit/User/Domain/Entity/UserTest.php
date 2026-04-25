<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserStatus;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCreateAssignsDefaults(): void
    {
        $now = new DateTimeImmutable('2026-01-01 10:00:00.000000');
        $user = User::create(
            UserId::new(),
            Email::fromString('john@example.com'),
            UserName::fromString('John'),
            $now,
        );

        self::assertSame(UserStatus::Active, $user->status());
        self::assertSame('john@example.com', $user->email()->value());
        self::assertSame('John', $user->name()->value());
        self::assertNull($user->deletedAt());
    }

    public function testChangeEmailUpdatesValue(): void
    {
        $now = new DateTimeImmutable('2026-01-01 10:00:00.000000');
        $later = new DateTimeImmutable('2026-01-01 11:00:00.000000');
        $user = User::create(
            UserId::new(),
            Email::fromString('john@example.com'),
            UserName::fromString('John'),
            $now,
        );

        $user->changeEmail(Email::fromString('new@example.com'), $later);

        self::assertSame('new@example.com', $user->email()->value());
        self::assertSame('2026-01-01 11:00:00.000000', $user->updatedAt()->format('Y-m-d H:i:s.u'));
    }

    public function testChangeStatus(): void
    {
        $now = new DateTimeImmutable('2026-01-01 10:00:00.000000');
        $later = new DateTimeImmutable('2026-01-01 11:00:00.000000');
        $user = User::create(
            UserId::new(),
            Email::fromString('john@example.com'),
            UserName::fromString('John'),
            $now,
        );

        $user->block($later);
        self::assertSame(UserStatus::Blocked, $user->status());

        $user->markPending($later);
        self::assertSame(UserStatus::Pending, $user->status());

        $user->activate($later);
        self::assertSame(UserStatus::Active, $user->status());
    }

    public function testSoftDelete(): void
    {
        $now = new DateTimeImmutable('2026-01-01 10:00:00.000000');
        $user = User::create(
            UserId::new(),
            Email::fromString('john@example.com'),
            UserName::fromString('John'),
            $now,
        );

        $deletedAt = new DateTimeImmutable('2026-01-01 11:00:00.000000');
        $user->delete($deletedAt);

        self::assertNotNull($user->deletedAt());
        self::assertTrue($user->isDeleted());
        self::assertSame('2026-01-01 11:00:00.000000', $user->deletedAt()?->format('Y-m-d H:i:s.u'));
    }
}
