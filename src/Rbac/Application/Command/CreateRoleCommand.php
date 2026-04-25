<?php

declare(strict_types=1);

namespace App\Rbac\Application\Command;

final readonly class CreateRoleCommand
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public bool $isSystem = false,
    ) {
    }
}
