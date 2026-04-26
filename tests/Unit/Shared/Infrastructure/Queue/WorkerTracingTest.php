<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Queue;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Infrastructure\Queue\JobHandlerInterface;
use App\Shared\Infrastructure\Queue\JobInterface;
use App\Shared\Infrastructure\Queue\JobRegistry;
use App\Shared\Infrastructure\Queue\QueueJobId;
use App\Shared\Infrastructure\Queue\QueueWorkerStorageInterface;
use App\Shared\Infrastructure\Queue\ReservedJob;
use App\Shared\Infrastructure\Queue\Worker;
use App\Shared\Infrastructure\Queue\WorkerOptions;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;

use function PHPUnit\Framework\assertTrue;

final class WorkerTracingTest extends Unit
{
    public function testWorkerLogsCompletedWithCorrelationId(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{message:string,context:array<string,mixed>}> */
            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = ['message' => (string) $message, 'context' => $context];
            }
        };

        $registry = new JobRegistry([
            TraceTestJob::TYPE => [
                'job' => TraceTestJob::class,
                'handler' => TraceTestJobHandler::class,
            ],
        ]);
        $container = new class implements ContainerInterface {
            public function get(string $id): object
            {
                return new TraceTestJobHandler();
            }

            public function has(string $id): bool
            {
                return true;
            }
        };

        $worker = new Worker(
            registry: $registry,
            container: $container,
            logger: $logger,
            activityLogger: new class implements ActivityLoggerInterface {
                public function log(ActivityLogEntry $entry): void {}
            },
        );

        $queue = new class implements QueueWorkerStorageInterface {
            private bool $taken = false;

            public function push(JobInterface $job, int $delaySeconds = 0, int $maxAttempts = 3): string
            {
                return QueueJobId::generate()->toString();
            }

            public function pushDelayed(JobInterface $job, int $delaySeconds, int $maxAttempts = 3): string
            {
                return QueueJobId::generate()->toString();
            }

            public function reserveNext(): ?ReservedJob
            {
                if ($this->taken) {
                    return null;
                }
                $this->taken = true;

                return new ReservedJob(
                    id: QueueJobId::generate(),
                    type: TraceTestJob::TYPE,
                    job: new TraceTestJob(),
                    attempt: 1,
                    maxAttempts: 3,
                    metadata: [
                        'request_id' => 'req-queue-1',
                        'correlation_id' => 'corr-queue-1',
                    ],
                );
            }

            public function markDone(ReservedJob $job): void {}

            public function release(ReservedJob $job, int $delaySeconds, string $lastError): void {}

            public function markFailed(ReservedJob $job, string $lastError): void {}
        };

        $worker->run($queue, new WorkerOptions(maxJobs: 1));

        assertTrue($this->hasRecord($logger->records, 'queue.job.started'));
        assertTrue($this->hasRecord($logger->records, 'queue.job.completed'));
        assertTrue($this->hasRecord($logger->records, 'corr-queue-1'));
    }

    public function testWorkerLogsFailedEvent(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{message:string,context:array<string,mixed>}> */
            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = ['message' => (string) $message, 'context' => $context];
            }
        };

        $registry = new JobRegistry([
            FailingTraceTestJob::TYPE => [
                'job' => FailingTraceTestJob::class,
                'handler' => FailingTraceTestJobHandler::class,
            ],
        ]);
        $container = new class implements ContainerInterface {
            public function get(string $id): object
            {
                return new FailingTraceTestJobHandler();
            }

            public function has(string $id): bool
            {
                return true;
            }
        };

        $worker = new Worker(
            registry: $registry,
            container: $container,
            logger: $logger,
            activityLogger: new class implements ActivityLoggerInterface {
                public function log(ActivityLogEntry $entry): void {}
            },
        );

        $queue = new class implements QueueWorkerStorageInterface {
            private bool $taken = false;

            public function push(JobInterface $job, int $delaySeconds = 0, int $maxAttempts = 3): string
            {
                return QueueJobId::generate()->toString();
            }

            public function pushDelayed(JobInterface $job, int $delaySeconds, int $maxAttempts = 3): string
            {
                return QueueJobId::generate()->toString();
            }

            public function reserveNext(): ?ReservedJob
            {
                if ($this->taken) {
                    return null;
                }
                $this->taken = true;

                return new ReservedJob(
                    id: QueueJobId::generate(),
                    type: FailingTraceTestJob::TYPE,
                    job: new FailingTraceTestJob(),
                    attempt: 1,
                    maxAttempts: 1,
                    metadata: ['request_id' => 'req-queue-2'],
                );
            }

            public function markDone(ReservedJob $job): void {}

            public function release(ReservedJob $job, int $delaySeconds, string $lastError): void {}

            public function markFailed(ReservedJob $job, string $lastError): void {}
        };

        $worker->run($queue, new WorkerOptions(maxJobs: 1));

        assertTrue($this->hasRecord($logger->records, 'queue.job.failed'));
        assertTrue($this->hasRecord($logger->records, 'req-queue-2'));
    }

    /**
     * @param list<array{message:string,context:array<string,mixed>}> $records
     */
    private function hasRecord(array $records, string $needle): bool
    {
        foreach ($records as $record) {
            if ($record['message'] === $needle) {
                return true;
            }

            foreach ($record['context'] as $value) {
                if ($value === $needle) {
                    return true;
                }
            }
        }

        return false;
    }
}

final class TraceTestJob implements JobInterface
{
    public const TYPE = 'trace.test.job';

    public static function fromPayload(array $payload): static
    {
        return new self();
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function toPayload(): array
    {
        return [];
    }
}

final class TraceTestJobHandler implements JobHandlerInterface
{
    public function handle(JobInterface $job): void {}
}

final class FailingTraceTestJob implements JobInterface
{
    public const TYPE = 'trace.test.failing.job';

    public static function fromPayload(array $payload): static
    {
        return new self();
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function toPayload(): array
    {
        return [];
    }
}

final class FailingTraceTestJobHandler implements JobHandlerInterface
{
    public function handle(JobInterface $job): void
    {
        throw new \RuntimeException('failed');
    }
}
