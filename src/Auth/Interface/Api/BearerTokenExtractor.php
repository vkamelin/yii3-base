<?php

declare(strict_types=1);

namespace App\Auth\Interface\Api;

use Psr\Http\Message\ServerRequestInterface;

use function preg_match;
use function trim;

final readonly class BearerTokenExtractor
{
    public function extract(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if ($header === '') {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
            return null;
        }

        $token = trim($matches[1]);
        return $token === '' ? null : $token;
    }
}
