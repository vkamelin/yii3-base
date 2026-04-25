<?php

declare(strict_types=1);

namespace App\Auth\Interface\Web\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class RedirectResponseFactory
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function to(string $path, int $statusCode = 302): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Location', $path);
    }
}
