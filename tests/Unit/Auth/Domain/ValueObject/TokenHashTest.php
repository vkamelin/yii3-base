<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Domain\ValueObject;

use App\Auth\Domain\ValueObject\TokenHash;
use Codeception\Test\Unit;
use InvalidArgumentException;

use function PHPUnit\Framework\assertSame;
use function strlen;

final class TokenHashTest extends Unit
{
    public function testCreateFromPlainToken(): void
    {
        $hash = TokenHash::fromPlainToken('sample-token');
        assertSame(32, strlen($hash->value()));
    }

    public function testInvalidHexThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TokenHash::fromHex('bad');
    }
}
