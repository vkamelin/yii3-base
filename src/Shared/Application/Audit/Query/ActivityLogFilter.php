<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Query;

final readonly class ActivityLogFilter
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $action = null,
        public ?string $actorUserId = null,
        public ?string $entityType = null,
        public ?string $requestId = null,
        public ?string $source = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}
}
