<?php

declare(strict_types=1);

namespace App\Auth\Domain\Repository;

use App\Auth\Domain\Entity\AuthToken;
use App\Auth\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;

interface AuthTokenRepositoryInterface
{
    public function save(AuthToken $token): void;

    public function findByHash(TokenHash $hash): ?AuthToken;

    public function revokeByHash(TokenHash $hash): void;

    public function revokeAllForUser(UserId $userId): void;
}
