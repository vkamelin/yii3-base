<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use Yiisoft\Db\Connection\ConnectionInterface;

use function is_array;

final readonly class MySqlUserRepository implements UserRepositoryInterface
{
    private const TABLE_NAME = '{{%users}}';

    public function __construct(
        private ConnectionInterface $connection,
        private UserHydrator $hydrator,
    ) {
    }

    public function save(User $user): void
    {
        $row = $this->hydrator->extract($user);
        $exists = $this->connection->createQuery()
            ->select(['id'])
            ->from(self::TABLE_NAME)
            ->where(['id' => $user->id()->value()])
            ->limit(1)
            ->one();

        if ($exists === null) {
            $this->connection->createCommand()->insert(self::TABLE_NAME, $row)->execute();
            return;
        }

        $this->connection->createCommand()->update(
            self::TABLE_NAME,
            [
                'email' => $row['email'],
                'email_normalized' => $row['email_normalized'],
                'name' => $row['name'],
                'status' => $row['status'],
                'updated_at' => $row['updated_at'],
                'deleted_at' => $row['deleted_at'],
            ],
            ['id' => $row['id']],
        )->execute();
    }

    public function findById(UserId $id): ?User
    {
        $row = $this->connection->createQuery()
            ->from(self::TABLE_NAME)
            ->where([
                'id' => $id->value(),
                'deleted_at' => null,
            ])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrator->hydrate($row);
    }

    public function findByEmail(Email $email): ?User
    {
        $row = $this->connection->createQuery()
            ->from(self::TABLE_NAME)
            ->where([
                'email_normalized' => $email->normalized(),
                'deleted_at' => null,
            ])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrator->hydrate($row);
    }

    public function existsByEmail(Email $email): bool
    {
        $row = $this->connection->createQuery()
            ->select(['id'])
            ->from(self::TABLE_NAME)
            ->where(['email_normalized' => $email->normalized()])
            ->limit(1)
            ->one();

        return $row !== null;
    }
}
