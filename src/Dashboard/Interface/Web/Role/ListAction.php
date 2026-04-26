<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\Role;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Rbac\Application\Query\ListRolesQuery;
use App\Rbac\Infrastructure\Persistence\MySqlRbacReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_scalar;
use function max;
use function trim;

final readonly class ListAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private MySqlRbacReadRepository $readRepository,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, (int) ($queryParams['per_page'] ?? 20));

        $search = $queryParams['search'] ?? null;
        $search = is_scalar($search) ? trim((string) $search) : null;
        $search = $search === '' ? null : $search;

        $isSystemRaw = $queryParams['is_system'] ?? null;
        $isSystemRaw = is_scalar($isSystemRaw) ? trim((string) $isSystemRaw) : null;
        $isSystem = match ($isSystemRaw) {
            '1' => true,
            '0' => false,
            default => null,
        };

        $query = new ListRolesQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
            isSystem: $isSystem,
        );

        return $this->viewRenderer->renderMain('Role/list', [
            'roles' => $this->readRepository->listRoles($query),
            'total' => $this->readRepository->countRoles($query),
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search ?? '',
            'isSystem' => $isSystemRaw ?? '',
        ]);
    }
}

