<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Persistence;

use App\Rbac\Domain\Entity\Permission;
use App\Rbac\Domain\ValueObject\PermissionCode;
use App\Rbac\Domain\ValueObject\PermissionId;
use DateTimeImmutable;
use RuntimeException;

use function is_scalar;
use function is_string;
use function sprintf;

final class PermissionHydrator
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @param array<string, mixed> $row
     */
    public function hydrate(array $row): Permission
    {
        return Permission::restore(
            id: PermissionId::fromString($this->stringValue($row, 'id')),
            code: PermissionCode::fromString($this->stringValue($row, 'code')),
            name: $this->stringValue($row, 'name'),
            groupCode: $this->stringValue($row, 'group_code'),
            description: $this->nullableString($row['description'] ?? null),
            isSystem: $this->boolValue($row, 'is_system'),
            createdAt: $this->parseDateTime($this->stringValue($row, 'created_at')),
            updatedAt: $this->parseDateTime($this->stringValue($row, 'updated_at')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(Permission $permission): array
    {
        return [
            'id' => $permission->id()->value(),
            'code' => $permission->code()->value(),
            'name' => $permission->name(),
            'description' => $permission->description(),
            'group_code' => $permission->groupCode(),
            'is_system' => $permission->isSystem() ? 1 : 0,
            'created_at' => $permission->createdAt()->format(self::DATETIME_FORMAT),
            'updated_at' => $permission->updatedAt()->format(self::DATETIME_FORMAT),
        ];
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

    /**
     * @param array<string, mixed> $row
     */
    private function boolValue(array $row, string $field): bool
    {
        $value = $row[$field] ?? null;

        return $value === true || $value === 1 || $value === '1';
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (!is_scalar($value)) {
            throw new RuntimeException('Expected nullable scalar value.');
        }

        return (string) $value;
    }

    private function parseDateTime(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $value);
        if ($date !== false) {
            return $date;
        }

        return new DateTimeImmutable($value);
    }
}
