<?php

declare(strict_types=1);

namespace App\Auth\Application\DTO;

final readonly class AuthResult
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $name,
        public string $status,
    ) {
    }
}
