<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Enum\UserStatus;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use DateTimeImmutable;
use DomainException;

final class User
{
    private function __construct(
        private UserId $id,
        private Email $email,
        private UserName $name,
        private UserStatus $status,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {}

    public static function create(
        UserId $id,
        Email $email,
        UserName $name,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            email: $email,
            name: $name,
            status: UserStatus::Active,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
        );
    }

    public static function restore(
        UserId $id,
        Email $email,
        UserName $name,
        UserStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self($id, $email, $name, $status, $createdAt, $updatedAt, $deletedAt);
    }

    public function rename(UserName $name, DateTimeImmutable $now): void
    {
        $this->guardNotDeleted('Deleted user cannot be renamed.');

        $this->name = $name;
        $this->updatedAt = $now;
    }

    public function changeEmail(Email $email, DateTimeImmutable $now): void
    {
        $this->guardNotDeleted('Deleted user cannot change email.');

        $this->email = $email;
        $this->updatedAt = $now;
    }

    public function activate(DateTimeImmutable $now): void
    {
        $this->guardNotDeleted('Deleted user cannot be activated.');

        $this->status = UserStatus::Active;
        $this->updatedAt = $now;
    }

    public function block(DateTimeImmutable $now): void
    {
        $this->guardNotDeleted('Deleted user cannot be blocked.');

        $this->status = UserStatus::Blocked;
        $this->updatedAt = $now;
    }

    public function markPending(DateTimeImmutable $now): void
    {
        $this->guardNotDeleted('Deleted user cannot be set to pending.');

        $this->status = UserStatus::Pending;
        $this->updatedAt = $now;
    }

    public function delete(DateTimeImmutable $now): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $this->deletedAt = $now;
        $this->updatedAt = $now;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function guardNotDeleted(string $message): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainException($message);
        }
    }
}
