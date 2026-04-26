<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Audit;

use App\Shared\Application\Audit\Query\ActivityLogFilter;
use App\Shared\Application\Audit\Query\ActivityLogPage;
use App\Shared\Application\Audit\Query\ActivityLogQueryInterface;
use App\Shared\Application\Audit\Query\ActivityLogView;
use App\Shared\Infrastructure\Persistence\UuidBinary;
use Ramsey\Uuid\Uuid;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function is_array;
use function is_string;
use function json_decode;
use function max;
use function min;
use function trim;

final readonly class MySqlActivityLogQuery implements ActivityLogQueryInterface
{
    private const TABLE_NAME = '{{%activity_logs}}';

    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function list(ActivityLogFilter $filter): ActivityLogPage
    {
        $page = max(1, $filter->page);
        $perPage = min(100, max(1, $filter->perPage));
        $offset = ($page - 1) * $perPage;

        $query = $this->baseQuery($filter);
        $rows = $query
            ->select([
                'id',
                'actor_user_id',
                'actor_type',
                'action',
                'entity_type',
                'entity_id',
                'ip',
                'user_agent',
                'request_id',
                'source',
                'payload',
                'created_at',
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($offset)
            ->limit($perPage)
            ->all();

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $items[] = $this->mapRow($row);
        }

        return new ActivityLogPage(
            items: $items,
            total: (int) $this->baseQuery($filter)->count('*'),
            page: $page,
            perPage: $perPage,
        );
    }

    public function getById(string $id): ?ActivityLogView
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        $row = $this->connection->createQuery()
            ->from(self::TABLE_NAME)
            ->where(['id' => UuidBinary::fromString($id)])
            ->limit(1)
            ->one();

        if (!is_array($row)) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function mapRow(array $row): ActivityLogView
    {
        return new ActivityLogView(
            id: is_string($row['id'] ?? null) ? UuidBinary::toString($row['id']) : '',
            actorUserId: is_string($row['actor_user_id'] ?? null) ? UuidBinary::toString($row['actor_user_id']) : null,
            actorType: (string) ($row['actor_type'] ?? ''),
            action: (string) ($row['action'] ?? ''),
            entityType: $this->nullableString($row['entity_type'] ?? null),
            entityId: is_string($row['entity_id'] ?? null) ? UuidBinary::toString($row['entity_id']) : null,
            ip: $this->nullableString($row['ip'] ?? null),
            userAgent: $this->nullableString($row['user_agent'] ?? null),
            requestId: $this->nullableString($row['request_id'] ?? null),
            source: (string) ($row['source'] ?? ''),
            payload: $this->decodePayload($row['payload'] ?? null),
            createdAt: (string) ($row['created_at'] ?? ''),
        );
    }

    /**
     * @return QueryInterface
     */
    private function baseQuery(ActivityLogFilter $filter)
    {
        $query = $this->connection->createQuery()->from(self::TABLE_NAME);

        if (($filter->action !== null) && trim($filter->action) !== '') {
            $query->andWhere(['action' => trim($filter->action)]);
        }

        if (($filter->actorUserId !== null) && trim($filter->actorUserId) !== '') {
            $actorUserId = trim($filter->actorUserId);
            if (Uuid::isValid($actorUserId)) {
                $query->andWhere(['actor_user_id' => UuidBinary::fromString($actorUserId)]);
            } else {
                $query->andWhere('0 = 1');
            }
        }

        if (($filter->entityType !== null) && trim($filter->entityType) !== '') {
            $query->andWhere(['entity_type' => trim($filter->entityType)]);
        }

        if (($filter->requestId !== null) && trim($filter->requestId) !== '') {
            $query->andWhere(['request_id' => trim($filter->requestId)]);
        }

        if (($filter->source !== null) && trim($filter->source) !== '') {
            $query->andWhere(['source' => trim($filter->source)]);
        }

        if (($filter->dateFrom !== null) && trim($filter->dateFrom) !== '') {
            $query->andWhere(['>=', 'created_at', trim($filter->dateFrom)]);
        }

        if (($filter->dateTo !== null) && trim($filter->dateTo) !== '') {
            $query->andWhere(['<=', 'created_at', trim($filter->dateTo)]);
        }

        return $query;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decodePayload(mixed $payload): ?array
    {
        if (is_array($payload)) {
            /** @var array<string,mixed> $payload */
            return $payload;
        }

        if (!is_string($payload) || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
