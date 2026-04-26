<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\Layout;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class DashboardViewRenderer
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    /**
     * @param array<string, mixed> $parameters
     */
    public function renderMain(string $view, array $parameters = []): ResponseInterface
    {
        return $this->rendererWithMainLayout()->render($view, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function renderEmpty(string $view, array $parameters = []): ResponseInterface
    {
        return $this->rendererWithEmptyLayout()->render($view, $parameters);
    }

    private function rendererWithMainLayout(): WebViewRenderer
    {
        return $this->viewRenderer
            ->withViewPath('@src/Dashboard/Interface/Web')
            ->withLayout('@src/Dashboard/Interface/Web/Layout/main.php');
    }

    private function rendererWithEmptyLayout(): WebViewRenderer
    {
        return $this->viewRenderer
            ->withViewPath('@src/Dashboard/Interface/Web')
            ->withLayout('@src/Dashboard/Interface/Web/Layout/empty.php');
    }
}
