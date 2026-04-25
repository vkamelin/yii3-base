<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Infrastructure\Security;

use App\Auth\Infrastructure\Security\RandomTokenGenerator;
use Codeception\Test\Unit;
use RuntimeException;

use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertTrue;

final class RandomTokenGeneratorTest extends Unit
{
    public function testGenerate(): void
    {
        $generator = new RandomTokenGenerator();
        $first = $generator->generate();
        $second = $generator->generate();

        assertTrue($first !== '');
        assertNotSame($first, $second);
    }

    public function testTooShortLengthThrows(): void
    {
        $this->expectException(RuntimeException::class);
        (new RandomTokenGenerator())->generate(8);
    }
}
