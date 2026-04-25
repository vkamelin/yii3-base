<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\UserName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserNameTest extends TestCase
{
    public function testCreatesValidUserName(): void
    {
        $name = UserName::fromString('John Doe');

        self::assertSame('John Doe', $name->value());
    }

    public function testThrowsOnEmptyUserName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserName::fromString('   ');
    }

    public function testThrowsOnTooLongUserName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserName::fromString(str_repeat('a', 161));
    }
}
