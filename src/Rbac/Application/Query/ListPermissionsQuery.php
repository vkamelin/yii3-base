<?php

declare(strict_types=1);

namespace App\Rbac\Application\Query;

final readonly class ListPermissionsQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $search = null,
        public ?string $groupCode = null,
        public ?bool $isSystem = null,
    ) {
    }
}
