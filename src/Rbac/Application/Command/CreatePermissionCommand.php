<?php

declare(strict_types=1);

namespace App\Rbac\Application\Command;

final readonly class CreatePermissionCommand
{
    public function __construct(
        public string $code,
        public string $name,
        public string $groupCode,
        public ?string $description = null,
        public bool $isSystem = false,
    ) {}
}
