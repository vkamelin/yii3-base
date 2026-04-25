<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Domain\ValueObject;

use App\Rbac\Domain\ValueObject\PermissionCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PermissionCodeTest extends TestCase
{
    public function testCreatesValidPermissionCode(): void
    {
        $code = PermissionCode::fromString('users.manage');

        self::assertSame('users.manage', $code->value());
    }

    public function testThrowsOnInvalidPermissionCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PermissionCode::fromString('users');
    }
}
