<?php

declare(strict_types=1);

namespace App\Auth\Application\Handler;

use App\Auth\Application\Command\LoginCommand;
use App\Auth\Application\DTO\AuthResult;
use App\Auth\Domain\Repository\UserCredentialsRepositoryInterface;
use App\Auth\Domain\Service\PasswordHasherInterface;
use App\Auth\Domain\ValueObject\PlainPassword;
use App\Shared\Application\Exception\AccessDeniedException;
use App\Shared\Application\Exception\InvalidCredentialsException;
use App\Shared\Application\Exception\ValidationException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\Email;
use Throwable;

final readonly class LoginHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserCredentialsRepositoryInterface $credentials,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(LoginCommand $command): AuthResult
    {
        return $this->handle($command);
    }

    public function handle(LoginCommand $command): AuthResult
    {
        try {
            $email = Email::fromString($command->email);
            $password = PlainPassword::fromString($command->password);
        } catch (Throwable $e) {
            throw new ValidationException($e->getMessage(), 0, $e);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if (!$user->status()->isActive()) {
            throw new AccessDeniedException('User is not active.');
        }

        $passwordHash = $this->credentials->findPasswordHashByUserId($user->id());
        if ($passwordHash === null) {
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if (!$this->passwordHasher->verify($password, $passwordHash)) {
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if ($this->passwordHasher->needsRehash($passwordHash)) {
            $this->credentials->savePasswordHash($user->id(), $this->passwordHasher->hash($password));
        }

        return new AuthResult(
            userId: $user->id()->value(),
            email: $user->email()->value(),
            name: $user->name()->value(),
            status: $user->status()->value,
        );
    }
}
