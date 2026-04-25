<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use App\Shared\Infrastructure\Queue\Exception\InvalidJobPayloadException;
use App\Shared\Infrastructure\Queue\Exception\QueueException;
use Yiisoft\Db\Connection\ConnectionInterface;

use function is_array;
use function is_string;
use function json_encode;
use function min;
use function sprintf;
use function str_contains;
use function strlen;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;

final class MySqlQueue implements QueueWorkerStorageInterface
{
    private const TABLE_NAME = '{{%queue_jobs}}';
    private const STATUS_PENDING = 'pending';
    private const STATUS_RESERVED = 'reserved';
    private const STATUS_DONE = 'done';
    private const STATUS_FAILED = 'failed';

    private bool $skipLockedSupported;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly JobSerializer $serializer,
        private readonly int $defaultMaxAttempts = 3,
        bool $skipLockedSupported = true,
    ) {
        $this->skipLockedSupported = $skipLockedSupported;
    }

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
        try {
            return $this->reserveInternal($this->skipLockedSupported);
        } catch (\Throwable $e) {
            if (!$this->skipLockedSupported || !$this->isSkipLockedError($e)) {
                throw new QueueException('Unable to reserve job from MySQL queue.', 0, $e);
            }

            $this->skipLockedSupported = false;
            return $this->reserveInternal(false);
        }
    }

    public function markDone(ReservedJob $job): void
    {
        $now = $this->now();

        try {
            $this->db->createCommand()->update(
                self::TABLE_NAME,
                [
                    'status' => self::STATUS_DONE,
                    'reserved_at' => null,
                    'updated_at' => $now,
                ],
                ['id' => $job->id->toBinary()],
            )->execute();
        } catch (\Throwable $e) {
            throw new QueueException(
                sprintf('Unable to mark queue job "%s" as done.', $job->id->toString()),
                0,
                $e,
            );
        }
    }

    public function release(ReservedJob $job, int $delaySeconds, string $lastError): void
    {
        $delaySeconds = max(0, $delaySeconds);
        $now = $this->now();
        $availableAt = $this->now($delaySeconds);

        try {
            $this->db->createCommand()->update(
                self::TABLE_NAME,
                [
                    'status' => self::STATUS_PENDING,
                    'available_at' => $availableAt,
                    'reserved_at' => null,
                    'updated_at' => $now,
                    'last_error' => $this->truncateError($lastError),
                ],
                ['id' => $job->id->toBinary()],
            )->execute();
        } catch (\Throwable $e) {
            throw new QueueException(
                sprintf('Unable to release queue job "%s" back to MySQL queue.', $job->id->toString()),
                0,
                $e,
            );
        }
    }

    public function markFailed(ReservedJob $job, string $lastError): void
    {
        $now = $this->now();

        try {
            $this->db->createCommand()->update(
                self::TABLE_NAME,
                [
                    'status' => self::STATUS_FAILED,
                    'failed_at' => $now,
                    'reserved_at' => null,
                    'updated_at' => $now,
                    'last_error' => $this->truncateError($lastError),
                ],
                ['id' => $job->id->toBinary()],
            )->execute();
        } catch (\Throwable $e) {
            throw new QueueException(
                sprintf('Unable to mark queue job "%s" as failed.', $job->id->toString()),
                0,
                $e,
            );
        }
    }

    private function reserveInternal(bool $useSkipLocked): ?ReservedJob
    {
        return $this->db->transaction(function (ConnectionInterface $db) use ($useSkipLocked): ?ReservedJob {
            $now = $this->now();
            $suffix = $useSkipLocked ? ' FOR UPDATE SKIP LOCKED' : ' FOR UPDATE';

            $sql = 'SELECT id, type, payload, attempts, max_attempts FROM ' . self::TABLE_NAME
                . ' WHERE status = :status AND available_at <= :available_at'
                . ' ORDER BY available_at ASC, created_at ASC LIMIT 1'
                . $suffix;

            $row = $db->createCommand($sql, [
                ':status' => self::STATUS_PENDING,
                ':available_at' => $now,
            ])->queryOne();

            if ($row === null) {
                return null;
            }

            $idBinary = $this->binaryIdFromRow($row);
            $attempt = ((int) ($row['attempts'] ?? 0)) + 1;
            $maxAttempts = max(1, (int) ($row['max_attempts'] ?? $this->defaultMaxAttempts));

            $affectedRows = $db->createCommand()->update(
                self::TABLE_NAME,
                [
                    'status' => self::STATUS_RESERVED,
                    'attempts' => $attempt,
                    'reserved_at' => $now,
                    'updated_at' => $now,
                ],
                ['id' => $idBinary, 'status' => self::STATUS_PENDING],
            )->execute();

            if ($affectedRows !== 1) {
                return null;
            }

            $payload = $this->payloadFromRow($row);
            $job = $this->serializer->deserialize($payload);
            $type = $this->typeFromRow($row);

            if ($job->type() !== $type) {
                throw new InvalidJobPayloadException(
                    sprintf('Job payload type mismatch for queue job "%s".', QueueJobId::fromBinary($idBinary)->toString()),
                );
            }

            return new ReservedJob(
                QueueJobId::fromBinary($idBinary),
                $type,
                $job,
                $attempt,
                $maxAttempts,
            );
        });
    }

    private function pushInternal(JobInterface $job, int $delaySeconds, int $maxAttempts): string
    {
        $delaySeconds = max(0, $delaySeconds);
        $maxAttempts = max(1, $maxAttempts);
        $id = QueueJobId::generate();
        $now = $this->now();
        $availableAt = $this->now($delaySeconds);

        try {
            $this->db->createCommand()->insert(
                self::TABLE_NAME,
                [
                    'id' => $id->toBinary(),
                    'type' => $job->type(),
                    'payload' => $this->serializer->serialize($job),
                    'status' => self::STATUS_PENDING,
                    'attempts' => 0,
                    'max_attempts' => $maxAttempts,
                    'available_at' => $availableAt,
                    'reserved_at' => null,
                    'failed_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'last_error' => null,
                ],
            )->execute();
        } catch (\Throwable $e) {
            throw new QueueException('Unable to push job to MySQL queue.', 0, $e);
        }

        return $id->toString();
    }

    private function now(int $plusSeconds = 0): string
    {
        return (new \DateTimeImmutable())
            ->modify(sprintf('+%d seconds', $plusSeconds))
            ->format('Y-m-d H:i:s.u');
    }

    /**
     * @param array<string,mixed> $row
     */
    private function binaryIdFromRow(array $row): string
    {
        $id = $row['id'] ?? null;

        if (!is_string($id) || $id === '') {
            throw new InvalidJobPayloadException('Queue row does not contain valid binary job id.');
        }

        return $id;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function typeFromRow(array $row): string
    {
        $type = $row['type'] ?? null;
        if (!is_string($type) || trim($type) === '') {
            throw new InvalidJobPayloadException('Queue row does not contain valid job type.');
        }

        return $type;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function payloadFromRow(array $row): string
    {
        $payload = $row['payload'] ?? null;

        if (is_string($payload)) {
            return $payload;
        }

        if (is_array($payload)) {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        }

        throw new InvalidJobPayloadException('Queue row does not contain valid payload.');
    }

    private function truncateError(string $error): string
    {
        return strlen($error) > 65535 ? substr($error, 0, min(65535, strlen($error))) : $error;
    }

    private function isSkipLockedError(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'SKIP LOCKED');
    }
}
