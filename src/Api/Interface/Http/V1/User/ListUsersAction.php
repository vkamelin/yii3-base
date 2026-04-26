<?php

declare(strict_types=1);

namespace App\Api\Interface\Http\V1\User;

use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\User\Application\Query\ListUsersQuery;
use App\User\Infrastructure\Persistence\UserReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_scalar;
use function max;
use function trim;

final readonly class ListUsersAction
{
    public function __construct(
        private UserReadRepository $userReadRepository,
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

        $status = $queryParams['status'] ?? null;
        $status = is_scalar($status) ? trim((string) $status) : null;
        $status = $status === '' ? null : $status;

        $query = new ListUsersQuery(
            page: $page,
            perPage: $perPage,
            search: $search,
            status: $status,
        );

        $items = $this->userReadRepository->list($query);
        $total = $this->userReadRepository->count($query);

        return $this->responseFactory->paginated($request, $items, $page, $perPage, $total);
    }
}
