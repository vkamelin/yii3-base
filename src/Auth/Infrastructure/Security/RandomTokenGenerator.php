<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Service\TokenGeneratorInterface;
use RuntimeException;

use function base64_encode;
use function random_bytes;
use function rtrim;
use function strtr;

final readonly class RandomTokenGenerator implements TokenGeneratorInterface
{
    public function generate(int $bytes = 32): string
    {
        if ($bytes < 16) {
            throw new RuntimeException('Token length is too short.');
        }

        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }
}
