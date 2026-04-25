<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Session;

use App\Auth\Application\Session\AuthSessionInterface;
use Yiisoft\Session\SessionInterface;

use function is_string;

final readonly class YiiSessionAuthStorage implements AuthSessionInterface
{
    private const USER_ID_KEY = 'auth.user_id';

    public function __construct(
        private SessionInterface $session,
    ) {
    }

    public function login(string $userId): void
    {
        $this->openSession();
        $this->session->set(self::USER_ID_KEY, $userId);
        $this->session->regenerateId();
    }

    public function logout(): void
    {
        $this->openSession();
        $this->session->remove(self::USER_ID_KEY);
        $this->session->regenerateId();
    }

    public function userId(): ?string
    {
        $this->openSession();
        $value = $this->session->get(self::USER_ID_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function openSession(): void
    {
        if (!$this->session->isActive()) {
            $this->session->open();
        }
    }
}
