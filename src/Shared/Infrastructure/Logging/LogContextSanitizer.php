<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use Stringable;

use function in_array;
use function is_array;
use function is_object;
use function is_scalar;
use function str_contains;
use function strtolower;

final class LogContextSanitizer
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'api_token',
        'authorization',
        'cookie',
        'set-cookie',
        'session',
        'session_id',
        '_csrf',
        'csrf',
        'csrf_token',
    ];

    /** @var list<string> */
    private const SENSITIVE_HEADER_KEYS = [
        'authorization',
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    /**
     * @param array<array-key,mixed> $context
     * @return array<array-key,mixed>
     */
    public function sanitize(array $context): array
    {
        $result = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if ($this->isSensitiveKey($normalizedKey)) {
                continue;
            }

            if ($normalizedKey === 'headers' && is_array($value)) {
                $result[$key] = $this->sanitizeHeaders($value);
                continue;
            }

            $result[$key] = $this->sanitizeValue($value);
        }

        return $result;
    }

    /**
     * @param array<array-key,mixed> $headers
     * @return array<array-key,mixed>
     */
    public function sanitizeHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $name => $value) {
            $headerName = strtolower((string) $name);
            if (in_array($headerName, self::SENSITIVE_HEADER_KEYS, true)) {
                continue;
            }

            $result[$name] = $this->sanitizeValue($value);
        }

        return $result;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitize($value);
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_object($value)) {
            return $value::class;
        }

        return null;
    }

    private function isSensitiveKey(string $key): bool
    {
        if (in_array($key, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
