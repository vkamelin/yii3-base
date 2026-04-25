<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rbac\Domain\ValueObject;

use App\Rbac\Domain\ValueObject\RoleCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RoleCodeTest extends TestCase
{
    public function testCreatesValidRoleCode(): void
    {
        $code = RoleCode::fromString('admin.role-1');

        self::assertSame('admin.role-1', $code->value());
    }

    public function testThrowsOnInvalidRoleCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RoleCode::fromString('Admin');
    }
}
