<?php

declare(strict_types=1);

namespace App\Auth\Domain\Service;

interface TokenGeneratorInterface
{
    public function generate(int $bytes = 32): string;
}
