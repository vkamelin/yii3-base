<?php

declare(strict_types=1);

namespace App\Auth\Application\DTO;

final readonly class ApiTokenResult
{
    public function __construct(
        public string $tokenId,
        public string $plainToken,
        public string $tokenType,
        public ?string $expiresAt,
    ) {
    }
}
