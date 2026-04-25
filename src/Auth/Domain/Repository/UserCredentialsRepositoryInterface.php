<?php

declare(strict_types=1);

namespace App\Auth\Domain\Repository;

use App\Auth\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserId;

interface UserCredentialsRepositoryInterface
{
    public function findPasswordHashByUserId(UserId $userId): ?PasswordHash;

    public function savePasswordHash(UserId $userId, PasswordHash $hash): void;
}
