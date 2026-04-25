<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use App\User\Application\DTO\UserListItem;
use App\User\Application\DTO\UserView;
use App\User\Application\Query\ListUsersQuery;
use App\User\Domain\ValueObject\UserId;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function is_array;
use function is_string;
use function max;
use function min;
use function trim;

final readonly class UserReadRepository
{
    private const TABLE_NAME = '{{%users}}';

    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    /**
     * @return list<UserListItem>
     */
    public function list(ListUsersQuery $query): array
    {
        $page = max(1, $query->page);
        $perPage = min(100, max(1, $query->perPage));
        $offset = ($page - 1) * $perPage;

        $dbQuery = $this->baseQuery($query)
            ->select(['id', 'email', 'name', 'status', 'created_at', 'updated_at'])
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($offset)
            ->limit($perPage);

        $rows = $dbQuery->all();
        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $result[] = new UserListItem(
                id: (string) ($row['id'] ?? ''),
                email: (string) ($row['email'] ?? ''),
                name: (string) ($row['name'] ?? ''),
                status: (string) ($row['status'] ?? ''),
                createdAt: (string) ($row['created_at'] ?? ''),
                updatedAt: (string) ($row['updated_at'] ?? ''),
            );
        }

        return $result;
    }

    public function count(ListUsersQuery $query): int
    {
        return (int) $this->baseQuery($query)->count('*');
    }

    public function getById(UserId $id): ?UserView
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

        return new UserView(
            id: (string) ($row['id'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            name: (string) ($row['name'] ?? ''),
            status: (string) ($row['status'] ?? ''),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            deletedAt: $this->nullableString($row['deleted_at'] ?? null),
        );
    }

    /**
     * @return QueryInterface
     */
    private function baseQuery(ListUsersQuery $query)
    {
        $dbQuery = $this->connection->createQuery()
            ->from(self::TABLE_NAME)
            ->where(['deleted_at' => null]);

        $status = $query->status !== null ? trim($query->status) : null;
        if ($status !== null && $status !== '') {
            $dbQuery->andWhere(['status' => $status]);
        }

        $search = $query->search !== null ? trim($query->search) : null;
        if ($search !== null && $search !== '') {
            $dbQuery->andWhere([
                'or',
                ['like', 'email', $search],
                ['like', 'email_normalized', $search],
                ['like', 'name', $search],
            ]);
        }

        return $dbQuery;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }
}
