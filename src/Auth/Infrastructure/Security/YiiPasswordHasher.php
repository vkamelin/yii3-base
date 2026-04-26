<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Security;

use App\Auth\Domain\Service\PasswordHasherInterface;
use App\Auth\Domain\ValueObject\PasswordHash;
use App\Auth\Domain\ValueObject\PlainPassword;
use Yiisoft\Security\PasswordHasher;

final readonly class YiiPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasher $passwordHasher = new PasswordHasher(),
    ) {}

    public function hash(PlainPassword $password): PasswordHash
    {
        return PasswordHash::fromString(
            $this->passwordHasher->hash($password->value()),
        );
    }

    public function verify(PlainPassword $password, PasswordHash $hash): bool
    {
        return $this->passwordHasher->validate($password->value(), $hash->value());
    }

    public function needsRehash(PasswordHash $hash): bool
    {
        return $this->passwordHasher->needsRehash($hash->value());
    }
}
