<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\Enum\UserStatus;
use App\User\Domain\ValueObject\UserId;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

use function mb_strtolower;
use function trim;

final readonly class ChangeUserStatusCommand
{
    private string $id;
    private string $status;

    public function __construct(
        string $id,
        string $status,
    ) {
        $id = trim($id);
        if ($id === '' || !Uuid::isValid($id)) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        $status = mb_strtolower(trim($status));
        if (UserStatus::tryFrom($status) === null) {
            throw new InvalidArgumentException('Invalid user status.');
        }

        $this->id = $id;
        $this->status = $status;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function userId(): UserId
    {
        return UserId::fromString($this->id);
    }

    public function userStatus(): UserStatus
    {
        return UserStatus::from($this->status);
    }
}
