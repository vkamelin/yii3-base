<?php

declare(strict_types=1);

namespace App\Auth\Application\Session;

interface AuthSessionInterface
{
    public function login(string $userId): void;

    public function logout(): void;

    public function userId(): ?string;
}
