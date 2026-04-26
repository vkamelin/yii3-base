<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Audit;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Infrastructure\Persistence\UuidBinary;
use Yiisoft\Db\Connection\ConnectionInterface;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class MySqlActivityLogger implements ActivityLoggerInterface
{
    private const TABLE_NAME = '{{%activity_logs}}';

    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function log(ActivityLogEntry $entry): void
    {
        $this->connection->createCommand()->insert(self::TABLE_NAME, [
            'id' => UuidBinary::fromString($entry->id),
            'actor_user_id' => $entry->actorUserId !== null ? UuidBinary::fromString($entry->actorUserId) : null,
            'actor_type' => $entry->actorType,
            'action' => $entry->action,
            'entity_type' => $entry->entityType,
            'entity_id' => $entry->entityId !== null ? UuidBinary::fromString($entry->entityId) : null,
            'ip' => $entry->ip,
            'user_agent' => $entry->userAgent,
            'request_id' => $entry->requestId,
            'source' => $entry->source,
            'payload' => $entry->payload === null ? null : json_encode($entry->payload, JSON_THROW_ON_ERROR),
            'created_at' => $entry->createdAt->format('Y-m-d H:i:s.u'),
        ])->execute();
    }
}
