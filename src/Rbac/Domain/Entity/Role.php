<?php

declare(strict_types=1);

namespace App\Rbac\Domain\Entity;

use App\Rbac\Domain\ValueObject\RoleCode;
use App\Rbac\Domain\ValueObject\RoleId;
use DateTimeImmutable;
use InvalidArgumentException;

final class Role
{
    private function __construct(
        private RoleId $id,
        private RoleCode $code,
        private string $name,
        private ?string $description,
        private bool $isSystem,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        RoleId $id,
        RoleCode $code,
        string $name,
        ?string $description,
        bool $isSystem,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            code: $code,
            name: self::normalizeName($name),
            description: self::normalizeDescription($description),
            isSystem: $isSystem,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function restore(
        RoleId $id,
        RoleCode $code,
        string $name,
        ?string $description,
        bool $isSystem,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            code: $code,
            name: self::normalizeName($name),
            description: self::normalizeDescription($description),
            isSystem: $isSystem,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function rename(string $name, DateTimeImmutable $now): void
    {
        $this->name = self::normalizeName($name);
        $this->updatedAt = $now;
    }

    public function changeDescription(?string $description, DateTimeImmutable $now): void
    {
        $this->description = self::normalizeDescription($description);
        $this->updatedAt = $now;
    }

    public function markAsSystem(DateTimeImmutable $now): void
    {
        if ($this->isSystem) {
            return;
        }

        $this->isSystem = true;
        $this->updatedAt = $now;
    }

    public function id(): RoleId
    {
        return $this->id;
    }

    public function code(): RoleCode
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Role name cannot be empty.');
        }

        return $name;
    }

    private static function normalizeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $description = trim($description);

        return $description === '' ? null : $description;
    }
}
