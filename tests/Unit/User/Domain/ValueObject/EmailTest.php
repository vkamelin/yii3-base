<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testCreatesValidEmail(): void
    {
        $email = Email::fromString('John.Doe+tag@Example.COM');

        self::assertSame('John.Doe+tag@Example.COM', $email->value());
    }

    public function testNormalizesEmail(): void
    {
        $email = Email::fromString('John.Doe+tag@Example.COM');

        self::assertSame('john.doe+tag@example.com', $email->normalized());
    }

    public function testThrowsOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::fromString('not-an-email');
    }
}
