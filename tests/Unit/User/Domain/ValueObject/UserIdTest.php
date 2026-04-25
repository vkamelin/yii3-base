<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\UserId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    public function testCreatesNewUuidId(): void
    {
        $id = UserId::new();

        self::assertNotSame('', $id->value());
        self::assertTrue(UserId::fromString($id->value())->equals($id));
    }

    public function testThrowsOnInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserId::fromString('invalid-id');
    }
}
