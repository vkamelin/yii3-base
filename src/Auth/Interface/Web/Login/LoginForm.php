<?php

declare(strict_types=1);

namespace App\Auth\Interface\Web\Login;

use function filter_var;
use function is_scalar;
use function strtolower;
use function trim;

use const FILTER_VALIDATE_EMAIL;

final class LoginForm
{
    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $remember = self::toBoolean($data['remember'] ?? false);

        return new self($email, $password, $remember);
    }

    private string $errorMessage = '';

    private function __construct(
        private string $email,
        private string $password,
        private bool $remember,
    ) {
    }

    public function validate(): bool
    {
        if ($this->email === '' || filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errorMessage = 'Enter a valid email.';
            return false;
        }

        if ($this->password === '') {
            $this->errorMessage = 'Password is required.';
            return false;
        }

        return true;
    }

    public function addError(string $message): void
    {
        $this->errorMessage = $message;
    }

    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function remember(): bool
    {
        return $this->remember;
    }

    private static function toBoolean(mixed $value): bool
    {
        if (is_scalar($value)) {
            $normalized = strtolower(trim((string) $value));
            return $normalized === '1' || $normalized === 'true' || $normalized === 'on' || $normalized === 'yes';
        }

        return false;
    }
}
