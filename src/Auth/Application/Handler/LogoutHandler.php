<?php

declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\LogoutCommand;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\TokenHash;
use App\Shared\Application\Exception\ValidationException;
use Throwable;

final readonly class LogoutHandler
{
    public function __construct(
        private AuthTokenRepositoryInterface $tokens,
    ) {
    }

    public function __invoke(LogoutCommand $command): void
    {
        $this->handle($command);
    }

    public function handle(LogoutCommand $command): void
    {
        if ($command->token === null) {
            return;
        }

        try {
            $hash = TokenHash::fromPlainToken($command->token);
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $this->tokens->revokeByHash($hash);
    }
}
