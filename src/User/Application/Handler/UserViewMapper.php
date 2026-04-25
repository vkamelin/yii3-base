<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\User\Application\DTO\UserView;
use App\User\Domain\Entity\User;

final class UserViewMapper
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public static function fromEntity(User $user): UserView
    {
        return new UserView(
            id: $user->id()->value(),
            email: $user->email()->value(),
            name: $user->name()->value(),
            status: $user->status()->value,
            createdAt: $user->createdAt()->format(self::DATETIME_FORMAT),
            updatedAt: $user->updatedAt()->format(self::DATETIME_FORMAT),
            deletedAt: $user->deletedAt()?->format(self::DATETIME_FORMAT),
        );
    }
}
