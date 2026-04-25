<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Persistence;

use App\Rbac\Domain\Entity\Permission;
use App\Rbac\Domain\Repository\PermissionRepositoryInterface;
use App\Rbac\Domain\ValueObject\PermissionCode;
use App\Rbac\Domain\ValueObject\PermissionId;
use App\Rbac\Domain\ValueObject\RoleId;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;
use Yiisoft\Db\Connection\ConnectionInterface;

use function is_array;

final readonly class MySqlPermissionRepository implements PermissionRepositoryInterface
{
    private const PERMISSION_TABLE = '{{%permissions}}';
    private const ROLE_PERMISSION_TABLE = '{{%role_permissions}}';
    private const USER_ROLE_TABLE = '{{%user_roles}}';

    public function __construct(
        private ConnectionInterface $connection,
        private PermissionHydrator $hydrator,
    ) {
    }

    public function save(Permission $permission): void
    {
        $row = $this->hydrator->extract($permission);
        $exists = $this->connection->createQuery()
            ->select(['id'])
            ->from(self::PERMISSION_TABLE)
            ->where(['id' => $permission->id()->value()])
            ->limit(1)
            ->one();

        if ($exists === null) {
            $this->connection->createCommand()->insert(self::PERMISSION_TABLE, $row)->execute();
            return;
        }

        $this->connection->createCommand()->update(
            self::PERMISSION_TABLE,
            [
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'group_code' => $row['group_code'],
                'is_system' => $row['is_system'],
                'updated_at' => $row['updated_at'],
            ],
            ['id' => $row['id']],
        )->execute();
    }

    public function findById(PermissionId $id): ?Permission
    {
        $row = $this->connection->createQuery()
            ->from(self::PERMISSION_TABLE)
            ->where(['id' => $id->value()])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrator->hydrate($row);
    }

    public function findByCode(PermissionCode $code): ?Permission
    {
        $row = $this->connection->createQuery()
            ->from(self::PERMISSION_TABLE)
            ->where(['code' => $code->value()])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->hydrator->hydrate($row);
    }

    public function existsByCode(PermissionCode $code): bool
    {
        $row = $this->connection->createQuery()
            ->select(['id'])
            ->from(self::PERMISSION_TABLE)
            ->where(['code' => $code->value()])
            ->limit(1)
            ->one();

        return $row !== null;
    }

    public function assignToRole(RoleId $roleId, PermissionId $permissionId): void
    {
        $this->connection->createCommand(
            'INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
             VALUES (:role_id, :permission_id, :created_at)
             ON DUPLICATE KEY UPDATE `created_at` = `created_at`',
            [
                ':role_id' => $roleId->value(),
                ':permission_id' => $permissionId->value(),
                ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s.u'),
            ],
        )->execute();
    }

    public function revokeFromRole(RoleId $roleId, PermissionId $permissionId): void
    {
        $this->connection->createCommand()->delete(
            self::ROLE_PERMISSION_TABLE,
            [
                'role_id' => $roleId->value(),
                'permission_id' => $permissionId->value(),
            ],
        )->execute();
    }

    public function findByUserId(UserId $userId): array
    {
        $rows = $this->connection->createQuery()
            ->select(['p.*'])
            ->distinct(true)
            ->from(['ur' => self::USER_ROLE_TABLE])
            ->innerJoin(['rp' => self::ROLE_PERMISSION_TABLE], 'rp.role_id = ur.role_id')
            ->innerJoin(['p' => self::PERMISSION_TABLE], 'p.id = rp.permission_id')
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
