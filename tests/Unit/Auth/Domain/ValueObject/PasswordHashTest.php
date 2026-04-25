<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Domain\ValueObject;

use App\Auth\Domain\ValueObject\PasswordHash;
use Codeception\Test\Unit;
use InvalidArgumentException;

use function PHPUnit\Framework\assertSame;
use function str_repeat;

final class PasswordHashTest extends Unit
{
    public function testCreate(): void
    {
        $hash = PasswordHash::fromString('$2y$13$Qf0f8u9QbJZr52y1gQwQf.nf6Izn8S2XXdI4Nb1jzYV8p3lw7rq0m');
        assertSame('$2y$13$Qf0f8u9QbJZr52y1gQwQf.nf6Izn8S2XXdI4Nb1jzYV8p3lw7rq0m', $hash->value());
    }

    public function testLongHashThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PasswordHash::fromString(str_repeat('a', 256));
    }
}
