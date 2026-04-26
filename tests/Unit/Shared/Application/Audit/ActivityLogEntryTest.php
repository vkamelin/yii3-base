<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Audit;

use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActorContext;
use Codeception\Test\Unit;
use Ramsey\Uuid\Uuid;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

final class ActivityLogEntryTest extends Unit
{
    public function testCreatesUserEntry(): void
    {
        $entry = ActivityLogEntry::user(
            action: 'auth.login.success',
            actorUserId: Uuid::uuid7()->toString(),
            entityType: 'user',
            entityId: Uuid::uuid7()->toString(),
            payload: ['email' => 'admin@example.com'],
            context: ActorContext::user(Uuid::uuid7()->toString(), ActorContext::SOURCE_WEB, 'req-1'),
        );

        assertTrue(Uuid::isValid($entry->id));
        assertSame('auth.login.success', $entry->action);
        assertSame(ActorContext::SOURCE_WEB, $entry->source);
        assertNotEmpty($entry->createdAt->format('Y-m-d H:i:s.u'));
    }

    public function testSensitiveFieldsAreRemovedFromPayload(): void
    {
        $entry = ActivityLogEntry::system(
            action: 'system.seed.executed',
            payload: [
                'email' => 'admin@example.com',
                'password' => 'secret',
                'authorization' => 'Bearer token',
                'session_id' => 'abc',
                'nested' => [
                    'csrf_token' => 'csrf',
                    'safe' => 'ok',
                ],
            ],
        );

        assertArrayHasKey('email', $entry->payload ?? []);
        assertNull($entry->payload['password'] ?? null);
        assertNull($entry->payload['authorization'] ?? null);
        assertNull($entry->payload['session_id'] ?? null);
        assertSame('ok', $entry->payload['nested']['safe'] ?? null);
        assertNull($entry->payload['nested']['csrf_token'] ?? null);
    }
}
