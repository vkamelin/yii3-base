<?php

declare(strict_types=1);

namespace App\Rbac\Application\Command;

final readonly class AssignRoleCommand
{
    public function __construct(
        public string $userId,
        public string $roleCode,
    ) {}
}
