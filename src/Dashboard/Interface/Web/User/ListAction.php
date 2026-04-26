<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\User;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\User\Application\Query\ListUsersQuery;
use App\User\Infrastructure\Persistence\UserReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_scalar;
use function max;
use function trim;

final readonly class ListAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private UserReadRepository $userReadRepository,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, (int) ($queryParams['per_page'] ?? 20));

        $search = $queryParams['search'] ?? null;
        $search = is_scalar($search) ? trim((string) $search) : null;
        $search = $search === '' ? null : $search;

        $status = $queryParams['status'] ?? null;
        $status = is_scalar($status) ? trim((string) $status) : null;
        $status = $status === '' ? null : $status;

        $query = new ListUsersQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
            status: $status,
        );

        return $this->viewRenderer->renderMain('User/list', [
            'users' => $this->userReadRepository->list($query),
            'total' => $this->userReadRepository->count($query),
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search ?? '',
            'status' => $status ?? '',
        ]);
    }
}
