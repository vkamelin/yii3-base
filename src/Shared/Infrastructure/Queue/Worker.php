<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\QueueAuditAction;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function function_exists;
use function memory_get_usage;
use function microtime;
use function sprintf;
use function usleep;

final class Worker
{
    private bool $shouldStop = false;

    public function __construct(
        private readonly JobRegistry $registry,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
        private readonly ActivityLoggerInterface $activityLogger,
    ) {}

    public function run(QueueWorkerStorageInterface $queue, WorkerOptions $options): WorkerResult
    {
        $this->shouldStop = false;
        $this->attachSignalHandlers();

        $processed = 0;
        $failed = 0;
        $startedAt = microtime(true);
        $stopReason = 'stopped';

        while (true) {
            $this->dispatchSignals();

            if ($this->shouldStop) {
                $stopReason = 'signal';
                break;
            }

            if ($options->maxJobs > 0 && $processed >= $options->maxJobs) {
                $stopReason = 'max-jobs';
                break;
            }

            if ($options->maxTimeSeconds > 0 && (microtime(true) - $startedAt) >= $options->maxTimeSeconds) {
                $stopReason = 'max-time';
                break;
            }

            if ($options->memoryLimitMb > 0 && $this->usedMemoryMb() >= $options->memoryLimitMb) {
                $stopReason = 'memory-limit';
                break;
            }

            try {
                $reservedJob = $queue->reserveNext();
            } catch (\Throwable $e) {
                $this->logger->error('Queue reserve failed.', ['exception' => $e]);
                $this->pause($options->sleepSeconds);
                continue;
            }

            if ($reservedJob === null) {
                $this->pause($options->sleepSeconds);
                continue;
            }

            $this->logger->info(
                'Queue job reserved.',
                [
                    'job_id' => $reservedJob->id->toString(),
                    'type' => $reservedJob->type,
                    'attempt' => $reservedJob->attempt,
                    'max_attempts' => $reservedJob->maxAttempts,
                    'queue' => $options->queueName,
                ],
            );
            $this->activityLogger->log(ActivityLogEntry::system(
                action: QueueAuditAction::JOB_STARTED,
                entityType: 'queue_job',
                entityId: $reservedJob->id->toString(),
                payload: [
                    'job_type' => $reservedJob->type,
                    'attempt' => $reservedJob->attempt,
                    'max_attempts' => $reservedJob->maxAttempts,
                    'queue' => $options->queueName,
                ],
                context: ActorContext::system(ActorContext::SOURCE_QUEUE),
            ));

            try {
                $handler = $this->registry->resolveHandler($reservedJob->job, $this->container);
                $handler->handle($reservedJob->job);
                $queue->markDone($reservedJob);
                $processed++;
                $this->activityLogger->log(ActivityLogEntry::system(
                    action: QueueAuditAction::JOB_COMPLETED,
                    entityType: 'queue_job',
                    entityId: $reservedJob->id->toString(),
                    payload: [
                        'job_type' => $reservedJob->type,
                        'attempt' => $reservedJob->attempt,
                    ],
                    context: ActorContext::system(ActorContext::SOURCE_QUEUE),
                ));
            } catch (\Throwable $e) {
                $failed++;
                $message = $this->formatError($e);

                if ($reservedJob->attempt >= $reservedJob->maxAttempts) {
                    $queue->markFailed($reservedJob, $message);
                    $this->activityLogger->log(ActivityLogEntry::system(
                        action: QueueAuditAction::JOB_FAILED,
                        entityType: 'queue_job',
                        entityId: $reservedJob->id->toString(),
                        payload: [
                            'job_type' => $reservedJob->type,
                            'attempt' => $reservedJob->attempt,
                            'max_attempts' => $reservedJob->maxAttempts,
                            'error' => $message,
                        ],
                        context: ActorContext::system(ActorContext::SOURCE_QUEUE),
                    ));
                } else {
                    $queue->release($reservedJob, $options->sleepSeconds, $message);
                    $this->activityLogger->log(ActivityLogEntry::system(
                        action: QueueAuditAction::JOB_RETRIED,
                        entityType: 'queue_job',
                        entityId: $reservedJob->id->toString(),
                        payload: [
                            'job_type' => $reservedJob->type,
                            'attempt' => $reservedJob->attempt,
                            'max_attempts' => $reservedJob->maxAttempts,
                            'error' => $message,
                        ],
                        context: ActorContext::system(ActorContext::SOURCE_QUEUE),
                    ));
                }

                $this->logger->error(
                    'Queue job processing failed.',
                    [
                        'job_id' => $reservedJob->id->toString(),
                        'type' => $reservedJob->type,
                        'attempt' => $reservedJob->attempt,
                        'max_attempts' => $reservedJob->maxAttempts,
                        'error' => $message,
                    ],
                );
            }
        }

        return new WorkerResult($processed, $failed, $stopReason);
    }

    private function attachSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal') || !function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function (): void {
            $this->shouldStop = true;
        });
        pcntl_signal(SIGINT, function (): void {
            $this->shouldStop = true;
        });
    }

    private function dispatchSignals(): void
    {
        if (!function_exists('pcntl_signal_dispatch')) {
            return;
        }

        pcntl_signal_dispatch();
    }

    private function pause(int $sleepSeconds): void
    {
        $sleepSeconds = max(0, $sleepSeconds);
        if ($sleepSeconds === 0) {
            return;
        }

        usleep($sleepSeconds * 1_000_000);
    }

    private function usedMemoryMb(): int
    {
        return (int) (memory_get_usage(true) / 1024 / 1024);
    }

    private function formatError(\Throwable $e): string
    {
        return sprintf('[%s] %s', $e::class, $e->getMessage());
    }
}
