<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Domain\Entity;

use App\Auth\Domain\Entity\AuthToken;
use App\Auth\Domain\Enum\AuthTokenType;
use App\Auth\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;
use Codeception\Test\Unit;
use DateTimeImmutable;
use DomainException;
use Ramsey\Uuid\Uuid;

use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class AuthTokenTest extends Unit
{
    public function testIssueAndMarkUsed(): void
    {
        $now = new DateTimeImmutable();
        $token = AuthToken::issue(
            id: Uuid::uuid7()->toString(),
            userId: UserId::new(),
            tokenHash: TokenHash::fromPlainToken('plain-token'),
            type: AuthTokenType::Api,
            name: 'API key',
            abilities: ['users.read'],
            expiresAt: null,
            now: $now,
        );

        $usedAt = $now->modify('+1 minute');
        $token->markUsed($usedAt);

        assertSame($usedAt, $token->lastUsedAt());
        assertSame('api', $token->type()->value);
    }

    public function testRevokedTokenCannotBeUsed(): void
    {
        $now = new DateTimeImmutable();
        $token = AuthToken::issue(
            id: Uuid::uuid7()->toString(),
            userId: UserId::new(),
            tokenHash: TokenHash::fromPlainToken('plain-token'),
            type: AuthTokenType::Api,
            name: null,
            abilities: null,
            expiresAt: null,
            now: $now,
        );

        $token->revoke($now);
        assertTrue($token->isRevoked());
        assertNotNull($token->revokedAt());

        $this->expectException(DomainException::class);
        $token->markUsed($now->modify('+1 second'));
    }
}
