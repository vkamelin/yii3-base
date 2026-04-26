<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\ActivityLog;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Shared\Application\Audit\Query\ActivityLogQueryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class ViewAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private ActivityLogQueryInterface $query,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(string $id): ResponseInterface
    {
        $item = $this->query->getById($id);
        if ($item === null) {
            return $this->responseFactory->createResponse(404);
        }

        return $this->viewRenderer->renderMain('ActivityLog/view', [
            'item' => $item,
        ]);
    }
}
