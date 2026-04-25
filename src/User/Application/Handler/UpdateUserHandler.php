<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Application\Exception\ConflictException;
use App\Shared\Application\Exception\NotFoundException;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\DTO\UserView;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): UserView
    {
        return $this->handle($command);
    }

    public function handle(UpdateUserCommand $command): UserView
    {
        $id = $command->id();
        $email = $command->email();
        $name = $command->name();

        $user = $this->users->findById($id);
        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        if (!$user->email()->equals($email) && $this->users->existsByEmail($email)) {
            throw new ConflictException('User with this email already exists.');
        }

        $now = new DateTimeImmutable();
        $user->changeEmail($email, $now);
        $user->rename($name, $now);
        $this->users->save($user);

        return UserViewMapper::fromEntity($user);
    }
}
