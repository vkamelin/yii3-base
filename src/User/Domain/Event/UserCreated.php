<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;

final readonly class UserCreated
{
    public function __construct(
        public UserId $userId,
        public Email $email,
        public DateTimeImmutable $occurredAt,
    ) {}
}
