<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Infrastructure\Security;

use App\Auth\Domain\ValueObject\PlainPassword;
use App\Auth\Infrastructure\Security\YiiPasswordHasher;
use Codeception\Test\Unit;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

final class YiiPasswordHasherTest extends Unit
{
    public function testHashAndVerify(): void
    {
        $hasher = new YiiPasswordHasher();
        $plain = PlainPassword::fromString('admin123456');
        $hash = $hasher->hash($plain);

        assertTrue($hasher->verify($plain, $hash));
        assertFalse($hasher->verify(PlainPassword::fromString('wrong-pass-123'), $hash));
    }
}
