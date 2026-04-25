<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\UserName;

final readonly class CreateUserCommand
{
    private Email $email;
    private UserName $name;

    public function __construct(
        string $email,
        string $name,
    ) {
        $this->email = Email::fromString($email);
        $this->name = UserName::fromString($name);
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
