<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Interface\Api;

use App\Auth\Interface\Api\BearerTokenExtractor;
use Codeception\Test\Unit;
use HttpSoft\Message\ServerRequest;

use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

final class BearerTokenExtractorTest extends Unit
{
    public function testReturnsNullWhenHeaderMissing(): void
    {
        $extractor = new BearerTokenExtractor();
        $request = new ServerRequest();

        assertNull($extractor->extract($request));
    }

    public function testReturnsNullForUnsupportedScheme(): void
    {
        $extractor = new BearerTokenExtractor();
        $request = (new ServerRequest())->withHeader('Authorization', 'Basic abc');

        assertNull($extractor->extract($request));
    }

    public function testExtractsBearerToken(): void
    {
        $extractor = new BearerTokenExtractor();
        $request = (new ServerRequest())->withHeader('Authorization', 'Bearer token-value');

        assertSame('token-value', $extractor->extract($request));
    }
}
