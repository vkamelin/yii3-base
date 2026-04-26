<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Persistence;

use App\Rbac\Domain\Entity\Role;
use App\Rbac\Domain\Repository\RoleRepositoryInterface;
use App\Rbac\Domain\ValueObject\RoleCode;
use App\Rbac\Domain\ValueObject\RoleId;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

use function is_array;

final readonly class MySqlRoleRepository implements RoleRepositoryInterface
{
    private const ROLE_TABLE = '{{%roles}}';
    private const USER_ROLE_TABLE = '{{%user_roles}}';

    public function __construct(
        private ConnectionInterface $connection,
        private RoleHydrator $hydrator,
    ) {}

    public function save(Role $role): void
    {
        $row = $this->hydrator->extract($role);
        $exists = $this->connection->createQuery()
            ->select(['id'])
            ->from(self::ROLE_TABLE)
            ->where(['id' => $role->id()->value()])
            ->limit(1)
            ->one();

        if ($exists === null) {
            $this->connection->createCommand()->insert(self::ROLE_TABLE, $row)->execute();
            return;
        }

        $this->connection->createCommand()->update(
            self::ROLE_TABLE,
            [
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'is_system' => $row['is_system'],
                'updated_at' => $row['updated_at'],
            ],
            ['id' => $row['id']],
        )->execute();
    }

    public function findById(RoleId $id): ?Role
    {
        $row = $this->connection->createQuery()
            ->from(self::ROLE_TABLE)
            ->where(['id' => $id->value()])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrator->hydrate($row);
    }

    public function findByCode(RoleCode $code): ?Role
    {
        $row = $this->connection->createQuery()
            ->from(self::ROLE_TABLE)
            ->where(['code' => $code->value()])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrator->hydrate($row);
    }

    public function existsByCode(RoleCode $code): bool
    {
        $row = $this->connection->createQuery()
            ->select(['id'])
            ->from(self::ROLE_TABLE)
            ->where(['code' => $code->value()])
            ->limit(1)
            ->one();

        return $row !== null;
    }

    public function assignToUser(UserId $userId, RoleId $roleId): void
    {
        $this->connection->createCommand(
            'INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`)
             VALUES (:user_id, :role_id, :created_at)
             ON DUPLICATE KEY UPDATE `created_at` = `created_at`',
            [
                ':user_id' => $userId->value(),
                ':role_id' => $roleId->value(),
                ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s.u'),
            ],
        )->execute();
    }

    public function revokeFromUser(UserId $userId, RoleId $roleId): void
    {
        $this->connection->createCommand()->delete(
            self::USER_ROLE_TABLE,
            [
                'user_id' => $userId->value(),
                'role_id' => $roleId->value(),
            ],
        )->execute();
    }

    public function findByUserId(UserId $userId): array
    {
        $rows = $this->connection->createQuery()
            ->select(['r.*'])
            ->from(['ur' => self::USER_ROLE_TABLE])
            ->innerJoin(['r' => self::ROLE_TABLE], 'r.id = ur.role_id')
            ->where(['ur.user_id' => $userId->value()])
            ->all();

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            /** @var array<string, mixed> $row */
            $result[] = $this->hydrator->hydrate($row);
        }

        return $result;
    }
}
