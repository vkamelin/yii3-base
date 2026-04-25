<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Domain\ValueObject;

use App\Auth\Domain\ValueObject\PlainPassword;
use Codeception\Test\Unit;
use InvalidArgumentException;

use function PHPUnit\Framework\assertSame;

final class PlainPasswordTest extends Unit
{
    public function testCreate(): void
    {
        $password = PlainPassword::fromString('secret123');
        assertSame('secret123', $password->value());
    }

    public function testTooShortPasswordThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainPassword::fromString('short');
    }
}
