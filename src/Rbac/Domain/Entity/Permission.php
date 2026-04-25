<?php

declare(strict_types=1);

namespace App\Rbac\Domain\Entity;

use App\Rbac\Domain\ValueObject\PermissionCode;
use App\Rbac\Domain\ValueObject\PermissionId;
use DateTimeImmutable;
use InvalidArgumentException;

final class Permission
{
    private function __construct(
        private PermissionId $id,
        private PermissionCode $code,
        private string $name,
        private ?string $description,
        private string $groupCode,
        private bool $isSystem,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        PermissionId $id,
        PermissionCode $code,
        string $name,
        string $groupCode,
        ?string $description,
        bool $isSystem,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            code: $code,
            name: self::normalizeName($name),
            description: self::normalizeDescription($description),
            groupCode: self::normalizeGroupCode($groupCode),
            isSystem: $isSystem,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function restore(
        PermissionId $id,
        PermissionCode $code,
        string $name,
        string $groupCode,
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
            groupCode: self::normalizeGroupCode($groupCode),
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

    public function id(): PermissionId
    {
        return $this->id;
    }

    public function code(): PermissionCode
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

    public function groupCode(): string
    {
        return $this->groupCode;
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
            throw new InvalidArgumentException('Permission name cannot be empty.');
        }

        return $name;
    }

    private static function normalizeGroupCode(string $groupCode): string
    {
        $groupCode = trim($groupCode);
        if ($groupCode === '') {
            throw new InvalidArgumentException('Permission group code cannot be empty.');
        }

        return $groupCode;
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
