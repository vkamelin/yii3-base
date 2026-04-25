<?php

declare(strict_types=1);

namespace App\Auth\Domain\Service;

use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\PlainPassword;

interface PasswordHasherInterface
{
    public function hash(PlainPassword $password): PasswordHash;

    public function verify(PlainPassword $password, PasswordHash $hash): bool;

    public function needsRehash(PasswordHash $hash): bool;
}
