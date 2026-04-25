<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Security;

use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\User\Domain\ValueObject\UserId;
use Yiisoft\Db\Connection\ConnectionInterface;

use function count;
use function in_array;
use function trim;

final readonly class MySqlAccessChecker implements AccessCheckerInterface
{
    private const USER_ROLE_TABLE = '{{%user_roles}}';
    private const ROLE_PERMISSION_TABLE = '{{%role_permissions}}';
    private const PERMISSION_TABLE = '{{%permissions}}';

    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function userHasPermission(UserId $userId, string $permissionCode): bool
    {
        $permissionCode = trim($permissionCode);
        if ($permissionCode === '') {
            return false;
        }

        $row = $this->connection->createQuery()
            ->select(['1'])
            ->from(['ur' => self::USER_ROLE_TABLE])
            ->innerJoin(['rp' => self::ROLE_PERMISSION_TABLE], 'rp.role_id = ur.role_id')
            ->innerJoin(['p' => self::PERMISSION_TABLE], 'p.id = rp.permission_id')
            ->where([
                'ur.user_id' => $userId->value(),
                'p.code' => $permissionCode,
            ])
            ->limit(1)
            ->one();

        return $row !== null;
    }

    public function userHasAnyPermission(UserId $userId, array $permissionCodes): bool
    {
        $codes = $this->normalizeCodes($permissionCodes);
        if ($codes === []) {
            return false;
        }

        $row = $this->connection->createQuery()
            ->select(['1'])
            ->from(['ur' => self::USER_ROLE_TABLE])
            ->innerJoin(['rp' => self::ROLE_PERMISSION_TABLE], 'rp.role_id = ur.role_id')
            ->innerJoin(['p' => self::PERMISSION_TABLE], 'p.id = rp.permission_id')
            ->where(['ur.user_id' => $userId->value()])
            ->andWhere(['in', 'p.code', $codes])
            ->limit(1)
            ->one();

        return $row !== null;
    }

    public function userHasAllPermissions(UserId $userId, array $permissionCodes): bool
    {
        $codes = $this->normalizeCodes($permissionCodes);
        if ($codes === []) {
            return false;
        }

        $found = (int) $this->connection->createQuery()
            ->from(['ur' => self::USER_ROLE_TABLE])
            ->innerJoin(['rp' => self::ROLE_PERMISSION_TABLE], 'rp.role_id = ur.role_id')
            ->innerJoin(['p' => self::PERMISSION_TABLE], 'p.id = rp.permission_id')
            ->where(['ur.user_id' => $userId->value()])
            ->andWhere(['in', 'p.code', $codes])
            ->count('DISTINCT p.code');

        return $found === count($codes);
    }

    /**
     * @param list<string> $permissionCodes
     *
     * @return list<string>
     */
    private function normalizeCodes(array $permissionCodes): array
    {
        $result = [];

        foreach ($permissionCodes as $permissionCode) {
            $code = trim($permissionCode);
            if ($code === '' || in_array($code, $result, true)) {
                continue;
            }

            $result[] = $code;
        }

        return $result;
    }
}
