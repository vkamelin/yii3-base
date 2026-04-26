<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\CsrfTokenMiddleware;

use function str_starts_with;

final readonly class SelectiveCsrfTokenMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $excludedPrefixes
     */
    public function __construct(
        private CsrfTokenMiddleware $csrfTokenMiddleware,
        private array $excludedPrefixes = ['/api', '/api/'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        foreach ($this->excludedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $handler->handle($request);
            }
        }

        return $this->csrfTokenMiddleware->process($request, $handler);
    }
}
