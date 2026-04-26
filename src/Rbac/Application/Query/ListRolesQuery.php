<?php

declare(strict_types=1);

namespace App\Rbac\Application\Query;

final readonly class ListRolesQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $search = null,
        public ?bool $isSystem = null,
    ) {}
}
