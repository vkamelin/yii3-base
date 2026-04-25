<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Application\Exception\ConflictException;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\DTO\UserView;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use DateTimeImmutable;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function __invoke(CreateUserCommand $command): UserView
    {
        return $this->handle($command);
    }

    public function handle(CreateUserCommand $command): UserView
    {
        $email = $command->email();
        $name = $command->name();

        if ($this->users->existsByEmail($email)) {
            throw new ConflictException('User with this email already exists.');
        }

        $now = new DateTimeImmutable();
        $user = User::create(UserId::new(), $email, $name, $now);
        $this->users->save($user);

        return UserViewMapper::fromEntity($user);
    }
}
