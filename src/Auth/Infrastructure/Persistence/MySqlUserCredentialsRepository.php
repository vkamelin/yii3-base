<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence;

use App\Auth\Domain\Repository\UserCredentialsRepositoryInterface;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

use function is_array;
use function is_string;

final readonly class MySqlUserCredentialsRepository implements UserCredentialsRepositoryInterface
{
    private const TABLE_NAME = '{{%user_credentials}}';
    private const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function findPasswordHashByUserId(UserId $userId): ?PasswordHash
    {
        $row = $this->connection->createQuery()
            ->select(['password_hash'])
            ->from(self::TABLE_NAME)
            ->where(['user_id' => $userId->value()])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        $passwordHash = $row['password_hash'] ?? null;
        if (!is_string($passwordHash) || $passwordHash === '') {
            return null;
        }

        return PasswordHash::fromString($passwordHash);
    }

    public function savePasswordHash(UserId $userId, PasswordHash $hash): void
    {
        $now = (new DateTimeImmutable())->format(self::DATETIME_FORMAT);
        $exists = $this->connection->createQuery()
            ->select(['user_id'])
            ->from(self::TABLE_NAME)
            ->where(['user_id' => $userId->value()])
            ->limit(1)
            ->one();

        if ($exists === null) {
            $this->connection->createCommand()->insert(self::TABLE_NAME, [
                'user_id' => $userId->value(),
                'password_hash' => $hash->value(),
                'password_changed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            return;
        }

        $this->connection->createCommand()->update(
            self::TABLE_NAME,
            [
                'password_hash' => $hash->value(),
                'password_changed_at' => $now,
                'updated_at' => $now,
            ],
            ['user_id' => $userId->value()],
        )->execute();
    }
}
