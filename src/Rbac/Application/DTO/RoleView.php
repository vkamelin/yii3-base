<?php

declare(strict_types=1);

namespace App\Rbac\Application\DTO;

final readonly class RoleView
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public ?string $description,
        public bool $isSystem,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
