<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Query;

final readonly class ActivityLogPage
{
    /**
     * @param list<ActivityLogView> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
