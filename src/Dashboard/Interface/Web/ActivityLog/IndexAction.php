<?php

declare(strict_types=1);

namespace App\Dashboard\Interface\Web\ActivityLog;

use App\Dashboard\Interface\Web\Layout\DashboardViewRenderer;
use App\Shared\Application\Audit\ActivityLogEntry;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Shared\Application\Audit\ActorContext;
use App\Shared\Application\Audit\Action\AdminAuditAction;
use App\Shared\Application\Audit\Query\ActivityLogFilter;
use App\Shared\Application\Audit\Query\ActivityLogQueryInterface;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function is_scalar;
use function max;
use function trim;

final readonly class IndexAction
{
    public function __construct(
        private DashboardViewRenderer $viewRenderer,
        private ActivityLogQueryInterface $query,
        private ActivityLoggerInterface $activityLogger,
        private RequestAuditContext $auditContext,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = max(1, (int) ($queryParams['per_page'] ?? 20));

        $action = $this->stringQuery($queryParams, 'action');
        $actorUserId = $this->stringQuery($queryParams, 'actor_user_id');
        $entityType = $this->stringQuery($queryParams, 'entity_type');
        $requestId = $this->stringQuery($queryParams, 'request_id');
        $source = $this->stringQuery($queryParams, 'source');
        $dateFrom = $this->stringQuery($queryParams, 'date_from');
        $dateTo = $this->stringQuery($queryParams, 'date_to');

        $filter = new ActivityLogFilter(
            page: $page,
            perPage: $perPage,
            action: $action,
            actorUserId: $actorUserId,
            entityType: $entityType,
            requestId: $requestId,
            source: $source,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        $result = $this->query->list($filter);
        $context = $this->auditContext->actor(
            defaultSource: ActorContext::SOURCE_WEB,
            defaultActorType: ActorContext::ACTOR_ADMIN,
        );
        $this->activityLogger->log(ActivityLogEntry::admin(
            action: AdminAuditAction::ACTIVITY_LOG_VIEWED,
            actorUserId: $context->userId,
            entityType: 'activity_log',
            payload: [
                'filters' => [
                    'action' => $action,
                    'actor_user_id' => $actorUserId,
                    'entity_type' => $entityType,
                    'request_id' => $requestId,
                    'source' => $source,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'page' => $page,
                'per_page' => $perPage,
            ],
            context: $context,
        ));

        return $this->viewRenderer->renderMain('ActivityLog/index', [
            'result' => $result,
            'filter' => $filter,
        ]);
    }

    /**
     * @param array<string,mixed> $queryParams
     */
    private function stringQuery(array $queryParams, string $key): ?string
    {
        $value = $queryParams[$key] ?? null;
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
