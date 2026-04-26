<?php

declare(strict_types=1);

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\Enum\AuthTokenType;
use App\Auth\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class AuthToken
{
    /**
     * @param list<string>|null $abilities
     */
    private function __construct(
        private string $id,
        private UserId $userId,
        private TokenHash $tokenHash,
        private AuthTokenType $type,
        private ?string $name,
        private ?array $abilities,
        private ?DateTimeImmutable $lastUsedAt,
        private ?DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param list<string>|null $abilities
     */
    public static function issue(
        string $id,
        UserId $userId,
        TokenHash $tokenHash,
        AuthTokenType $type,
        ?string $name,
        ?array $abilities,
        ?DateTimeImmutable $expiresAt,
        DateTimeImmutable $now,
    ): self {
        self::assertId($id);
        self::assertAbilities($abilities);

        return new self(
            id: $id,
            userId: $userId,
            tokenHash: $tokenHash,
            type: $type,
            name: $name,
            abilities: $abilities,
            lastUsedAt: null,
            expiresAt: $expiresAt,
            revokedAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param list<string>|null $abilities
     */
    public static function restore(
        string $id,
        UserId $userId,
        TokenHash $tokenHash,
        AuthTokenType $type,
        ?string $name,
        ?array $abilities,
        ?DateTimeImmutable $lastUsedAt,
        ?DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $revokedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        self::assertId($id);
        self::assertAbilities($abilities);

        return new self(
            $id,
            $userId,
            $tokenHash,
            $type,
            $name,
            $abilities,
            $lastUsedAt,
            $expiresAt,
            $revokedAt,
            $createdAt,
            $updatedAt,
        );
    }

    public function revoke(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            return;
        }

        $this->revokedAt = $now;
        $this->updatedAt = $now;
    }

    public function markUsed(DateTimeImmutable $now): void
    {
        if ($this->isRevoked()) {
            throw new DomainException('Revoked token cannot be used.');
        }

        if ($this->isExpired($now)) {
            throw new DomainException('Expired token cannot be used.');
        }

        $this->lastUsedAt = $now;
        $this->updatedAt = $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= $now;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function tokenHash(): TokenHash
    {
        return $this->tokenHash;
    }

    public function type(): AuthTokenType
    {
        return $this->type;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * @return list<string>|null
     */
    public function abilities(): ?array
    {
        return $this->abilities;
    }

    public function lastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function assertId(string $id): void
    {
        if (!Uuid::isValid($id)) {
            throw new InvalidArgumentException('Invalid token ID.');
        }
    }

    /**
     * @param list<string>|null $abilities
     */
    private static function assertAbilities(?array $abilities): void
    {
        if ($abilities === null) {
            return;
        }

        foreach ($abilities as $ability) {
            if ($ability === '') {
                throw new InvalidArgumentException('Token abilities must be a list of non-empty strings.');
            }
        }
    }
}
