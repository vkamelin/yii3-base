<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Query;

final readonly class ActivityLogView
{
    /**
     * @param array<string,mixed>|null $payload
     */
    public function __construct(
        public string $id,
        public ?string $actorUserId,
        public string $actorType,
        public string $action,
        public ?string $entityType,
        public ?string $entityId,
        public ?string $ip,
        public ?string $userAgent,
        public ?string $requestId,
        public string $source,
        public ?array $payload,
        public string $createdAt,
    ) {}
}
