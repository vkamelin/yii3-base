<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\Rbac;

use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\Rbac\Application\Query\ListRolesQuery;
use App\Rbac\Infrastructure\Persistence\MySqlRbacReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_scalar;
use function max;
use function trim;

final readonly class ListRolesAction
{
    public function __construct(
        private MySqlRbacReadRepository $rbacReadRepository,
        private ApiResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, (int) ($queryParams['per_page'] ?? 20));

        $search = $queryParams['search'] ?? null;
        $search = is_scalar($search) ? trim((string) $search) : null;
        $search = $search === '' ? null : $search;

        $isSystem = null;
        if (isset($queryParams['is_system'])) {
            $isSystem = (bool) (int) $queryParams['is_system'];
        }

        $query = new ListRolesQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
            isSystem: $isSystem,
        );

        $items = $this->rbacReadRepository->listRoles($query);
        $total = $this->rbacReadRepository->countRoles($query);

        return $this->responseFactory->paginated($request, $items, $page, $perPage, $total);
    }
}
