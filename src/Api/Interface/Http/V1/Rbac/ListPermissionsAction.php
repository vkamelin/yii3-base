<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\Rbac;

use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\Rbac\Application\Query\ListPermissionsQuery;
use App\Rbac\Infrastructure\Persistence\MySqlRbacReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_scalar;
use function max;
use function trim;

final readonly class ListPermissionsAction
{
    public function __construct(
        private MySqlRbacReadRepository $rbacReadRepository,
        private ApiResponseFactory $responseFactory,
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

        $isSystem = null;
        if (isset($queryParams['is_system'])) {
            $isSystem = (bool) (int) $queryParams['is_system'];
        }

        $query = new ListPermissionsQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
            groupCode: $groupCode,
            isSystem: $isSystem,
        );

        $items = $this->rbacReadRepository->listPermissions($query);
        $total = $this->rbacReadRepository->countPermissions($query);

        return $this->responseFactory->paginated($request, $items, $page, $perPage, $total);
    }
}
