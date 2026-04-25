<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;

final readonly class UpdateUserCommand
{
    private UserId $id;
    private Email $email;
    private UserName $name;

    public function __construct(
        string $id,
        string $email,
        string $name,
    ) {
        $this->id = UserId::fromString($id);
        $this->email = Email::fromString($email);
        $this->name = UserName::fromString($name);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): UserName
    {
        return $this->name;
    }
}
