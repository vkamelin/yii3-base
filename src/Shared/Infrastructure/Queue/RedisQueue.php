<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use App\Shared\Application\Tracing\TraceContextProviderInterface;
use App\Shared\Infrastructure\Queue\Exception\InvalidJobPayloadException;
use App\Shared\Infrastructure\Queue\Exception\QueueException;
use Predis\ClientInterface;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function max;
use function sprintf;
use function time;

use const JSON_THROW_ON_ERROR;

/**
 * Transient Redis queue implementation.
 *
 * Limitations:
 * - This implementation is not durable enough for critical workflows.
 * - Acknowledgement and retry are best-effort and can lose in-flight tasks on crash/network split.
 * - Delayed scheduling is implemented via sorted set and is eventually consistent.
 */
final readonly class RedisQueue implements QueueWorkerStorageInterface
{
    public function __construct(
        private ClientInterface $redis,
        private JobSerializer $serializer,
        private TraceContextProviderInterface $traceContextProvider,
        private int $defaultMaxAttempts = 3,
        private string $keyPrefix = 'queue',
    ) {}

    public function push(JobInterface $job, int $delaySeconds = 0, int $maxAttempts = 3): string
    {
        return $this->pushInternal($job, $delaySeconds, $maxAttempts);
    }

    public function pushDelayed(JobInterface $job, int $delaySeconds, int $maxAttempts = 3): string
    {
        return $this->pushInternal($job, $delaySeconds, $maxAttempts);
    }

    public function reserveNext(): ?ReservedJob
    {
        $this->moveDueDelayedJobs();

        try {
            /** @var string|null $raw */
            $raw = $this->redis->lpop($this->pendingKey());
        } catch (\Throwable $e) {
            throw new QueueException('Unable to read job from Redis queue.', 0, $e);
        }

        if ($raw === null) {
            return null;
        }

        $meta = $this->decodeMeta($raw);
        $attempt = ((int) ($meta['attempt'] ?? 0)) + 1;
        $maxAttempts = max(1, (int) ($meta['max_attempts'] ?? $this->defaultMaxAttempts));
        $payload = $meta['payload'] ?? null;
        $id = $meta['id'] ?? null;

        if (!is_string($payload) || !is_string($id) || $id === '') {
            throw new InvalidJobPayloadException('Invalid Redis queue job payload.');
        }

        $job = $this->serializer->deserialize($payload);
        $metadata = $this->serializer->extractMetadata($payload);

        return new ReservedJob(
            QueueJobId::fromString($id),
            $job->type(),
            $job,
            $attempt,
            $maxAttempts,
            $metadata,
        );
    }

    public function markDone(ReservedJob $job): void
    {
        // No-op for transient Redis implementation.
    }

    public function release(ReservedJob $job, int $delaySeconds, string $lastError): void
    {
        try {
            $this->pushMeta(
                [
                    'id' => $job->id->toString(),
                    'attempt' => $job->attempt,
                    'max_attempts' => $job->maxAttempts,
                    'payload' => $this->serializer->serialize($job->job, $job->metadata),
                    'last_error' => $lastError,
                ],
                max(0, $delaySeconds),
            );
        } catch (\Throwable $e) {
            throw new QueueException(
                sprintf('Unable to release Redis queue job "%s".', $job->id->toString()),
                0,
                $e,
            );
        }
    }

    public function markFailed(ReservedJob $job, string $lastError): void
    {
        try {
            $this->redis->rpush(
                $this->failedKey(),
                [
                    json_encode(
                        [
                            'id' => $job->id->toString(),
                            'type' => $job->type,
                            'attempt' => $job->attempt,
                            'max_attempts' => $job->maxAttempts,
                            'payload' => $this->serializer->serialize($job->job, $job->metadata),
                            'metadata' => $job->metadata,
                            'failed_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                            'last_error' => $lastError,
                        ],
                        JSON_THROW_ON_ERROR,
                    ),
                ],
            );
        } catch (\Throwable $e) {
            throw new QueueException(
                sprintf('Unable to mark Redis queue job "%s" as failed.', $job->id->toString()),
                0,
                $e,
            );
        }
    }

    private function pushInternal(JobInterface $job, int $delaySeconds, int $maxAttempts): string
    {
        $id = QueueJobId::generate();
        $metadata = $this->buildTraceMetadata();

        $this->pushMeta(
            [
                'id' => $id->toString(),
                'attempt' => 0,
                'max_attempts' => max(1, $maxAttempts),
                'payload' => $this->serializer->serialize($job, $metadata),
            ],
            max(0, $delaySeconds),
        );

        return $id->toString();
    }

    /**
     * @param array<string,mixed> $meta
     */
    private function pushMeta(array $meta, int $delaySeconds): void
    {
        $encoded = json_encode($meta, JSON_THROW_ON_ERROR);

        try {
            if ($delaySeconds > 0) {
                $this->redis->zadd($this->delayedKey(), [(string) (time() + $delaySeconds) => $encoded]);
                return;
            }

            $this->redis->rpush($this->pendingKey(), [$encoded]);
        } catch (\Throwable $e) {
            throw new QueueException('Unable to push job to Redis queue.', 0, $e);
        }
    }

    private function moveDueDelayedJobs(): void
    {
        $now = time();

        try {
            /** @var list<string> $items */
            $items = $this->redis->zrangebyscore($this->delayedKey(), '-inf', (string) $now);
        } catch (\Throwable $e) {
            throw new QueueException('Unable to read delayed jobs from Redis queue.', 0, $e);
        }

        foreach ($items as $item) {
            $removed = $this->redis->zrem($this->delayedKey(), $item);
            if ($removed === 1) {
                $this->redis->rpush($this->pendingKey(), [$item]);
            }
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeMeta(string $raw): array
    {
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidJobPayloadException('Invalid Redis queue payload JSON.', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new InvalidJobPayloadException('Invalid Redis queue payload structure.');
        }

        /** @var array<string,mixed> $decoded */
        return $decoded;
    }

    private function pendingKey(): string
    {
        return $this->keyPrefix . ':pending';
    }

    private function delayedKey(): string
    {
        return $this->keyPrefix . ':delayed';
    }

    private function failedKey(): string
    {
        return $this->keyPrefix . ':failed';
    }

    /**
     * @return array<string,mixed>
     */
    private function buildTraceMetadata(): array
    {
        $traceContext = $this->traceContextProvider->get();

        return [
            'request_id' => $traceContext->requestId(),
            'correlation_id' => $traceContext->correlationId(),
            'source' => $traceContext->source(),
            'user_id' => $traceContext->userId(),
        ];
    }
}
