<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Application\Exception\NotFoundException;
use App\Shared\Application\Exception\ValidationException;
use App\User\Application\Command\ChangeUserStatusCommand;
use App\User\Application\DTO\UserView;
use App\User\Domain\Enum\UserStatus;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Throwable;

final readonly class ChangeUserStatusHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function __invoke(ChangeUserStatusCommand $command): UserView
    {
        return $this->handle($command);
    }

    public function handle(ChangeUserStatusCommand $command): UserView
    {
        try {
            $id = $command->userId();
            $status = $command->userStatus();
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            throw new NotFoundException('User not found.');
        }

        $now = new DateTimeImmutable();
        match ($status) {
            UserStatus::Active => $user->activate($now),
            UserStatus::Blocked => $user->block($now),
            UserStatus::Pending => $user->markPending($now),
        };

        $this->users->save($user);
        return UserViewMapper::fromEntity($user);
    }
}
