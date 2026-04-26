<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class UserView
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {}
}
