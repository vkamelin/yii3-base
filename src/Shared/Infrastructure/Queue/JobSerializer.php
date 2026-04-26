<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

use App\Shared\Infrastructure\Queue\Exception\InvalidJobPayloadException;

use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class JobSerializer
{
    public function __construct(
        private JobRegistry $registry,
    ) {}

    /**
     * @param array<string,mixed> $metadata
     */
    public function serialize(JobInterface $job, array $metadata = []): string
    {
        return json_encode(
            [
                'type' => $job->type(),
                'payload' => $job->toPayload(),
                'metadata' => $metadata,
            ],
            JSON_THROW_ON_ERROR,
        );
    }

    public function deserialize(string $encodedPayload): JobInterface
    {
        $decoded = $this->decodeToArray($encodedPayload);
        return $this->registry->createFromEnvelope($decoded);
    }

    /**
     * @return array<string,mixed>
     */
    public function decodeToArray(string $encodedPayload): array
    {
        try {
            $decoded = json_decode($encodedPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new InvalidJobPayloadException('Queue payload is not valid JSON.', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new InvalidJobPayloadException('Queue payload must decode to object.');
        }

        if (!isset($decoded['type']) || !is_string($decoded['type']) || $decoded['type'] === '') {
            throw new InvalidJobPayloadException('Queue payload does not contain valid job type.');
        }

        if (!isset($decoded['payload']) || !is_array($decoded['payload'])) {
            throw new InvalidJobPayloadException('Queue payload does not contain valid job payload.');
        }

        if (isset($decoded['metadata']) && !is_array($decoded['metadata'])) {
            throw new InvalidJobPayloadException('Queue payload metadata must be an object.');
        }

        /** @var array<string,mixed> $decoded */
        return $decoded;
    }

    /**
     * @return array<array-key,mixed>
     */
    public function extractMetadata(string $encodedPayload): array
    {
        return $this->metadataFromEnvelope($this->decodeToArray($encodedPayload));
    }

    /**
     * @param array<string,mixed> $envelope
     * @return array<array-key,mixed>
     */
    public function metadataFromEnvelope(array $envelope): array
    {
        $metadata = $envelope['metadata'] ?? [];
        return is_array($metadata) ? $metadata : [];
    }
}
