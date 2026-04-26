<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Logging;

use App\Shared\Infrastructure\Logging\LogContextSanitizer;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertArrayNotHasKey;
use function PHPUnit\Framework\assertSame;

final class LogContextSanitizerTest extends Unit
{
    public function testRemovesSensitiveFields(): void
    {
        $sanitizer = new LogContextSanitizer();
        $context = $sanitizer->sanitize([
            'email' => 'admin@example.com',
            'password' => 'secret',
            'payload' => [
                'access_token' => 'token',
                'safe' => 'value',
            ],
            'headers' => [
                'Authorization' => 'Bearer raw',
                'X-Request-Id' => 'req-1',
            ],
        ]);

        assertSame('admin@example.com', $context['email']);
        assertArrayNotHasKey('password', $context);
        assertArrayNotHasKey('access_token', $context['payload']);
        assertSame('value', $context['payload']['safe']);
        assertArrayNotHasKey('Authorization', $context['headers']);
        assertSame('req-1', $context['headers']['X-Request-Id']);
    }
}
