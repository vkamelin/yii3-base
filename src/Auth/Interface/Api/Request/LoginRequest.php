<?php

declare(strict_types=1);

namespace App\Auth\Interface\Api\Request;

use function filter_var;
use function is_string;
use function mb_strlen;
use function trim;

use const FILTER_VALIDATE_EMAIL;

final class LoginRequest
{
    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        $tokenName = null;
        if (isset($payload['token_name'])) {
            $tokenNameValue = $payload['token_name'];
            if (is_string($tokenNameValue)) {
                $tokenName = trim($tokenNameValue);
                if ($tokenName === '') {
                    $tokenName = null;
                }
            }
        }

        $errors = [];

        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Email must be a valid email address.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        }

        if (isset($payload['token_name'])) {
            if (!is_string($payload['token_name'])) {
                $errors['token_name'][] = 'Token name must be a string.';
            } elseif ($tokenName !== null && mb_strlen($tokenName) > 120) {
                $errors['token_name'][] = 'Token name must not exceed 120 characters.';
            }
        }

        return new self($email, $password, $tokenName, $errors);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function __construct(
        private string $email,
        private string $password,
        private ?string $tokenName,
        private array $errors,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function tokenName(): ?string
    {
        return $this->tokenName;
    }
}
