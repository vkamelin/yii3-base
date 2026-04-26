<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\Permission;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Rbac\Application\Query\ListPermissionsQuery;
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

        $groupCode = $queryParams['group_code'] ?? null;
        $groupCode = is_scalar($groupCode) ? trim((string) $groupCode) : null;
        $groupCode = $groupCode === '' ? null : $groupCode;

        $isSystemRaw = $queryParams['is_system'] ?? null;
        $isSystemRaw = is_scalar($isSystemRaw) ? trim((string) $isSystemRaw) : null;
        $isSystem = match ($isSystemRaw) {
            '1' => true,
            '0' => false,
            default => null,
        };

        $query = new ListPermissionsQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
            groupCode: $groupCode,
            isSystem: $isSystem,
        );

        return $this->viewRenderer->renderMain('Permission/list', [
            'permissions' => $this->readRepository->listPermissions($query),
            'total' => $this->readRepository->countPermissions($query),
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search ?? '',
            'groupCode' => $groupCode ?? '',
            'isSystem' => $isSystemRaw ?? '',
        ]);
    }
}

