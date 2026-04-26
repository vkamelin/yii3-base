<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Persistence;

use App\Rbac\Application\DTO\PermissionView;
use App\Rbac\Application\DTO\RoleView;
use App\Rbac\Application\DTO\UserPermissionView;
use App\Rbac\Application\Query\GetUserPermissionsQuery;
use App\Rbac\Application\Query\ListPermissionsQuery;
use App\Rbac\Application\Query\ListRolesQuery;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function is_array;
use function max;
use function min;
use function trim;

final readonly class MySqlRbacReadRepository
{
    private const ROLE_TABLE = '{{%roles}}';
    private const PERMISSION_TABLE = '{{%permissions}}';
    private const USER_ROLE_TABLE = '{{%user_roles}}';
    private const ROLE_PERMISSION_TABLE = '{{%role_permissions}}';

    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    /**
     * @return list<RoleView>
     */
    public function listRoles(ListRolesQuery $query): array
    {
        $page = max(1, $query->page);
        $perPage = min(100, max(1, $query->perPage));

        $rows = $this->rolesBaseQuery($query)
            ->select(['id', 'code', 'name', 'description', 'is_system', 'created_at', 'updated_at'])
            ->orderBy(['created_at' => SORT_DESC])
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result[] = new RoleView(
                id: (string) ($row['id'] ?? ''),
                code: (string) ($row['code'] ?? ''),
                name: (string) ($row['name'] ?? ''),
                description: $this->nullableString($row['description'] ?? null),
                isSystem: (bool) ((int) ($row['is_system'] ?? 0)),
                createdAt: (string) ($row['created_at'] ?? ''),
                updatedAt: (string) ($row['updated_at'] ?? ''),
            );
        }

        return $result;
    }

    public function countRoles(ListRolesQuery $query): int
    {
        return (int) $this->rolesBaseQuery($query)->count('*');
    }

    /**
     * @return list<PermissionView>
     */
    public function listPermissions(ListPermissionsQuery $query): array
    {
        $page = max(1, $query->page);
        $perPage = min(100, max(1, $query->perPage));

        $rows = $this->permissionsBaseQuery($query)
            ->select(['id', 'code', 'name', 'description', 'group_code', 'is_system', 'created_at', 'updated_at'])
            ->orderBy(['created_at' => SORT_DESC])
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->all();

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result[] = new PermissionView(
                id: (string) ($row['id'] ?? ''),
                code: (string) ($row['code'] ?? ''),
                name: (string) ($row['name'] ?? ''),
                description: $this->nullableString($row['description'] ?? null),
                groupCode: (string) ($row['group_code'] ?? ''),
                isSystem: (bool) ((int) ($row['is_system'] ?? 0)),
                createdAt: (string) ($row['created_at'] ?? ''),
                updatedAt: (string) ($row['updated_at'] ?? ''),
            );
        }

        return $result;
    }

    public function countPermissions(ListPermissionsQuery $query): int
    {
        return (int) $this->permissionsBaseQuery($query)->count('*');
    }

    /**
     * @return list<UserPermissionView>
     */
    public function listUserPermissions(GetUserPermissionsQuery $query): array
    {
        $rows = $this->connection->createQuery()
            ->select(['ur.user_id', 'r.code AS role_code', 'p.code AS permission_code'])
            ->from(['ur' => self::USER_ROLE_TABLE])
            ->innerJoin(['r' => self::ROLE_TABLE], 'r.id = ur.role_id')
            ->innerJoin(['rp' => self::ROLE_PERMISSION_TABLE], 'rp.role_id = ur.role_id')
            ->innerJoin(['p' => self::PERMISSION_TABLE], 'p.id = rp.permission_id')
            ->where(['ur.user_id' => $query->userId])
            ->orderBy(['r.code' => SORT_ASC, 'p.code' => SORT_ASC])
            ->all();

        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result[] = new UserPermissionView(
                userId: (string) ($row['user_id'] ?? ''),
                roleCode: (string) ($row['role_code'] ?? ''),
                permissionCode: (string) ($row['permission_code'] ?? ''),
            );
        }

        return $result;
    }

    public function countUserPermissions(GetUserPermissionsQuery $query): int
    {
        return (int) $this->connection->createQuery()
            ->from(['ur' => self::USER_ROLE_TABLE])
            ->innerJoin(['rp' => self::ROLE_PERMISSION_TABLE], 'rp.role_id = ur.role_id')
            ->where(['ur.user_id' => $query->userId])
            ->count('rp.permission_id');
    }

    /**
     * @return QueryInterface
     */
    private function rolesBaseQuery(ListRolesQuery $query)
    {
        $dbQuery = $this->connection->createQuery()->from(self::ROLE_TABLE);

        if ($query->isSystem !== null) {
            $dbQuery->andWhere(['is_system' => $query->isSystem ? 1 : 0]);
        }

        $search = $query->search !== null ? trim($query->search) : null;
        if ($search !== null && $search !== '') {
            $dbQuery->andWhere([
                'or',
                ['like', 'code', $search],
                ['like', 'name', $search],
                ['like', 'description', $search],
            ]);
        }

        return $dbQuery;
    }

    /**
     * @return QueryInterface
     */
    private function permissionsBaseQuery(ListPermissionsQuery $query)
    {
        $dbQuery = $this->connection->createQuery()->from(self::PERMISSION_TABLE);

        if ($query->isSystem !== null) {
            $dbQuery->andWhere(['is_system' => $query->isSystem ? 1 : 0]);
        }

        $groupCode = $query->groupCode !== null ? trim($query->groupCode) : null;
        if ($groupCode !== null && $groupCode !== '') {
            $dbQuery->andWhere(['group_code' => $groupCode]);
        }

        $search = $query->search !== null ? trim($query->search) : null;
        if ($search !== null && $search !== '') {
            $dbQuery->andWhere([
                'or',
                ['like', 'code', $search],
                ['like', 'name', $search],
                ['like', 'description', $search],
            ]);
        }

        return $dbQuery;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
