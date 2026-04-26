<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Query\ActivityLogFilter;
use App\Shared\Infrastructure\Audit\MySqlActivityLogQuery;
use App\Shared\Infrastructure\Audit\MySqlActivityLogger;
use App\Tests\Support\FunctionalTester;
use PDO;
use Ramsey\Uuid\Uuid;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;

use function getenv;
use function sprintf;

use function PHPUnit\Framework\assertGreaterThanOrEqual;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertSame;

final class AuditInfrastructureCest
{
    public function mySqlActivityLoggerWritesRecord(FunctionalTester $tester): void
    {
        $connection = $this->createConnection();
        $pdo = $this->createPdo();
        $logger = new MySqlActivityLogger($connection);

        $requestId = 'test-logger-' . Uuid::uuid7()->toString();
        $logger->log(ActivityLogEntry::system(
            action: 'test.audit.logger.write',
            entityType: 'test',
            payload: ['email' => 'admin@example.com'],
            context: new ActorContext(
                userId: null,
                actorType: ActorContext::ACTOR_SYSTEM,
                source: ActorContext::SOURCE_SYSTEM,
                requestId: $requestId,
            ),
        ));

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM activity_logs WHERE request_id = :request_id');
        $stmt->execute([':request_id' => $requestId]);
        $count = (int) $stmt->fetchColumn();

        assertGreaterThanOrEqual(1, $count);
    }

    public function mySqlActivityLogQueryFiltersByActionSourceRequestId(FunctionalTester $tester): void
    {
        $connection = $this->createConnection();
        $logger = new MySqlActivityLogger($connection);
        $query = new MySqlActivityLogQuery($connection);

        $requestId = 'test-query-' . Uuid::uuid7()->toString();
        $logger->log(ActivityLogEntry::system(
            action: 'test.audit.query.target',
            entityType: 'test',
            payload: ['kind' => 'target'],
            context: new ActorContext(
                userId: null,
                actorType: ActorContext::ACTOR_SYSTEM,
                source: ActorContext::SOURCE_QUEUE,
                requestId: $requestId,
            ),
        ));
        $logger->log(ActivityLogEntry::system(
            action: 'test.audit.query.other',
            entityType: 'test',
            payload: ['kind' => 'other'],
            context: new ActorContext(
                userId: null,
                actorType: ActorContext::ACTOR_SYSTEM,
                source: ActorContext::SOURCE_SYSTEM,
                requestId: 'other-request',
            ),
        ));

        $page = $query->list(new ActivityLogFilter(
            page: 1,
            perPage: 20,
            action: 'test.audit.query.target',
            requestId: $requestId,
            source: ActorContext::SOURCE_QUEUE,
        ));

        assertGreaterThanOrEqual(1, $page->total);
        $first = $page->items[0] ?? null;
        assertNotNull($first);
        assertSame('test.audit.query.target', $first->action);
        assertSame($requestId, $first->requestId);
        assertSame(ActorContext::SOURCE_QUEUE, $first->source);
    }

    private function createConnection(): ConnectionInterface
    {
        return new Connection(
            new Driver(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    getenv('DB_HOST') ?: 'mysql',
                    getenv('DB_PORT') ?: '3306',
                    getenv('DB_NAME') ?: 'app',
                ),
                getenv('DB_USER') ?: 'app',
                getenv('DB_PASSWORD') ?: 'app',
            ),
            new SchemaCache(new ArrayCache()),
        );
    }

    private function createPdo(): PDO
    {
        return new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'mysql',
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME') ?: 'app',
            ),
            getenv('DB_USER') ?: 'app',
            getenv('DB_PASSWORD') ?: 'app',
        );
    }
}
