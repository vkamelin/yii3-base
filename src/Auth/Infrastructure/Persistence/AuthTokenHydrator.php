<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Domain\Entity\AuthToken;
use App\Auth\Domain\Enum\AuthTokenType;
use App\Auth\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use JsonException;
use RuntimeException;

use function array_is_list;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class AuthTokenHydrator
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @param array<string, mixed> $row
     */
    public function hydrate(array $row): AuthToken
    {
        return AuthToken::restore(
            id: $this->stringValue($row, 'id'),
            userId: UserId::fromString($this->stringValue($row, 'user_id')),
            tokenHash: TokenHash::fromHex($this->stringValue($row, 'token_hash')),
            type: AuthTokenType::from($this->stringValue($row, 'type')),
            name: $this->nullableString($row['name'] ?? null),
            abilities: $this->abilitiesFromStorage($row['abilities'] ?? null),
            lastUsedAt: $this->nullableDateTime($row['last_used_at'] ?? null),
            expiresAt: $this->nullableDateTime($row['expires_at'] ?? null),
            revokedAt: $this->nullableDateTime($row['revoked_at'] ?? null),
            createdAt: $this->parseDateTime($this->stringValue($row, 'created_at')),
            updatedAt: $this->parseDateTime($this->stringValue($row, 'updated_at')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(AuthToken $token): array
    {
        return [
            'id' => $token->id(),
            'user_id' => $token->userId()->value(),
            'token_hash' => $token->tokenHash()->value(),
            'type' => $token->type()->value,
            'name' => $token->name(),
            'abilities' => $this->abilitiesToStorage($token->abilities()),
            'last_used_at' => $token->lastUsedAt()?->format(self::DATETIME_FORMAT),
            'expires_at' => $token->expiresAt()?->format(self::DATETIME_FORMAT),
            'revoked_at' => $token->revokedAt()?->format(self::DATETIME_FORMAT),
            'created_at' => $token->createdAt()->format(self::DATETIME_FORMAT),
            'updated_at' => $token->updatedAt()->format(self::DATETIME_FORMAT),
        ];
    }

    private function parseDateTime(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $value);
        if ($date !== false) {
            return $date;
        }

        return new DateTimeImmutable($value);
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

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new RuntimeException('Expected nullable string value.');
        }

        return $value;
    }

    /**
     * @return list<string>|null
     */
    private function abilitiesFromStorage(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new RuntimeException('Token abilities payload must be a JSON string.');
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Invalid token abilities JSON.', 0, $e);
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new RuntimeException('Token abilities must be a JSON list.');
        }

        foreach ($decoded as $ability) {
            if (!is_string($ability) || $ability === '') {
                throw new RuntimeException('Token abilities must contain non-empty strings only.');
            }
        }

        /** @var list<string> $decoded */
        return $decoded;
    }

    /**
     * @param list<string>|null $abilities
     */
    private function abilitiesToStorage(?array $abilities): ?string
    {
        if ($abilities === null) {
            return null;
        }

        try {
            return json_encode($abilities, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Unable to encode token abilities.', 0, $e);
        }
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
