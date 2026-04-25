<?php

declare(strict_types=1);

namespace App\Rbac\Application\Query;

final readonly class GetUserPermissionsQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
