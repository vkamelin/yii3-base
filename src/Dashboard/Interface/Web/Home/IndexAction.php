<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\Home;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use Psr\Http\Message\ResponseInterface;

final readonly class IndexAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->renderMain('Home/index');
    }
}

