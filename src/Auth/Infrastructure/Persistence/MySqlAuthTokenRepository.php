<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Domain\Entity\AuthToken;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

use function is_array;

final readonly class MySqlAuthTokenRepository implements AuthTokenRepositoryInterface
{
    private const TABLE_NAME = '{{%auth_tokens}}';
    private const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public function __construct(
        private ConnectionInterface $connection,
        private AuthTokenHydrator $hydrator,
    ) {
    }

    public function save(AuthToken $token): void
    {
        $row = $this->hydrator->extract($token);
        $exists = $this->connection->createQuery()
            ->select(['id'])
            ->from(self::TABLE_NAME)
            ->where(['id' => $token->id()])
            ->limit(1)
            ->one();

        if ($exists === null) {
            $this->connection->createCommand()->insert(self::TABLE_NAME, $row)->execute();
            return;
        }

        $this->connection->createCommand()->update(
            self::TABLE_NAME,
            [
                'user_id' => $row['user_id'],
                'token_hash' => $row['token_hash'],
                'type' => $row['type'],
                'name' => $row['name'],
                'abilities' => $row['abilities'],
                'last_used_at' => $row['last_used_at'],
                'expires_at' => $row['expires_at'],
                'revoked_at' => $row['revoked_at'],
                'updated_at' => $row['updated_at'],
            ],
            ['id' => $row['id']],
        )->execute();
    }

    public function findByHash(TokenHash $hash): ?AuthToken
    {
        $row = $this->connection->createQuery()
            ->from(self::TABLE_NAME)
            ->where(['token_hash' => $hash->value()])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrator->hydrate($row);
    }

    public function revokeByHash(TokenHash $hash): void
    {
        $now = (new DateTimeImmutable())->format(self::DATETIME_FORMAT);
        $this->connection->createCommand()->update(
            self::TABLE_NAME,
            [
                'revoked_at' => $now,
                'updated_at' => $now,
            ],
            ['token_hash' => $hash->value()],
        )->execute();
    }

    public function revokeAllForUser(UserId $userId): void
    {
        $now = (new DateTimeImmutable())->format(self::DATETIME_FORMAT);
        $this->connection->createCommand()->update(
            self::TABLE_NAME,
            [
                'revoked_at' => $now,
                'updated_at' => $now,
            ],
            ['user_id' => $userId->value()],
        )->execute();
    }
}
