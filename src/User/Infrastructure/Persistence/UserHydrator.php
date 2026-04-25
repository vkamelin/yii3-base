<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserStatus;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use DateTimeImmutable;
use RuntimeException;

use function is_string;
use function sprintf;

final class UserHydrator
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @param array<string, mixed> $row
     */
    public function hydrate(array $row): User
    {
        return User::restore(
            id: UserId::fromString($this->stringValue($row, 'id')),
            email: Email::fromString($this->stringValue($row, 'email')),
            name: UserName::fromString($this->stringValue($row, 'name')),
            status: UserStatus::from($this->stringValue($row, 'status')),
            createdAt: $this->parseDateTime($this->stringValue($row, 'created_at')),
            updatedAt: $this->parseDateTime($this->stringValue($row, 'updated_at')),
            deletedAt: $this->nullableDateTime($row['deleted_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(User $user): array
    {
        return [
            'id' => $user->id()->value(),
            'email' => $user->email()->value(),
            'email_normalized' => $user->email()->normalized(),
            'name' => $user->name()->value(),
            'status' => $user->status()->value,
            'created_at' => $user->createdAt()->format(self::DATETIME_FORMAT),
            'updated_at' => $user->updatedAt()->format(self::DATETIME_FORMAT),
            'deleted_at' => $user->deletedAt()?->format(self::DATETIME_FORMAT),
        ];
    }

    private function nullableDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new RuntimeException('Invalid datetime value type.');
        }

        return $this->parseDateTime($value);
    }

    private function parseDateTime(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $value);
        if ($date !== false) {
            return $date;
        }

        return new DateTimeImmutable($value);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function stringValue(array $row, string $field): string
    {
        $value = $row[$field] ?? null;
        if (!is_string($value)) {
            throw new RuntimeException(sprintf('Field "%s" must be string.', $field));
        }

        return $value;
    }
}
