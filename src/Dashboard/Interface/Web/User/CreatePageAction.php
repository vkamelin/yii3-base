<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\User;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use Psr\Http\Message\ResponseInterface;

final readonly class CreatePageAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer->renderMain('User/create', [
            'form' => [
                'email' => '',
                'name' => '',
                'status' => 'active',
            ],
            'errors' => [],
        ]);
    }
}
