<?php

declare(strict_types=1);

namespace App\User\Domain\Enum;

enum UserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Pending = 'pending';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
