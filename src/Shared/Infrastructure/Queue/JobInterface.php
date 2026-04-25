<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Queue;

interface JobInterface
{
    /**
     * Hydrates a job from payload produced by {@see toPayload()}.
     *
     * @param array<string,mixed> $payload
     */
    public static function fromPayload(array $payload): static;

    public function type(): string;

    /**
     * Returns JSON-serializable payload.
     *
     * @return array<string,mixed>
     */
    public function toPayload(): array;
}
