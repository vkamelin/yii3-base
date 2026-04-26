<?php

declare(strict_types=1);

use App\Shared\Application\Audit\Query\ActivityLogFilter;
use App\Shared\Application\Audit\Query\ActivityLogPage;
use Yiisoft\Html\Html;

/**
 * @var ActivityLogPage $result
 * @var ActivityLogFilter $filter
 */

$headers = ['Created At', 'Source', 'Action', 'Actor', 'Entity', 'Request ID', ''];
$rows = [];

foreach ($result->items as $item) {
    $rows[] = [
        $item->createdAt,
        ['html' => '<span class="badge text-bg-secondary">' . Html::encode($item->source) . '</span>'],
        ['html' => '<span class="badge text-bg-primary">' . Html::encode($item->action) . '</span>'],
        $item->actorUserId ?? '-',
        ($item->entityType ?? '-') . ($item->entityId !== null ? ' #' . $item->entityId : ''),
        $item->requestId ?? '-',
        [
            'html' => '<a class="btn btn-sm btn-outline-primary" href="/dashboard/activity-log/' . Html::encode($item->id) . '">View</a>',
        ],
    ];
}
?>
<div class="row row-cards">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Activity Log</h3>
            </div>
        </div>
    </div>
    <div class="col-12">
        <?php
        $fields = [
            ['name' => 'action', 'label' => 'Action', 'value' => $filter->action ?? '', 'type' => 'text'],
            ['name' => 'actor_user_id', 'label' => 'Actor User ID', 'value' => $filter->actorUserId ?? '', 'type' => 'text'],
            ['name' => 'entity_type', 'label' => 'Entity Type', 'value' => $filter->entityType ?? '', 'type' => 'text'],
            ['name' => 'request_id', 'label' => 'Request ID', 'value' => $filter->requestId ?? '', 'type' => 'text'],
            [
                'name' => 'source',
                'label' => 'Source',
                'value' => $filter->source ?? '',
                'type' => 'select',
                'options' => [
                    'web' => 'web',
                    'api' => 'api',
                    'console' => 'console',
                    'system' => 'system',
                    'queue' => 'queue',
                ],
            ],
            ['name' => 'date_from', 'label' => 'Date From', 'value' => $filter->dateFrom ?? '', 'type' => 'datetime-local'],
            ['name' => 'date_to', 'label' => 'Date To', 'value' => $filter->dateTo ?? '', 'type' => 'datetime-local'],
            ['name' => 'per_page', 'label' => 'Per Page', 'value' => (string) $result->perPage, 'type' => 'text'],
        ];
$action = '/dashboard/activity-log';
require __DIR__ . '/../Layout/Partial/filter-form.php';
?>
    </div>
    <div class="col-12">
        <?php require __DIR__ . '/../Layout/Partial/table.php'; ?>
        <?php
$page = $result->page;
$perPage = $result->perPage;
$total = $result->total;
$path = '/dashboard/activity-log';
$query = [
    'action' => $filter->action,
    'actor_user_id' => $filter->actorUserId,
    'entity_type' => $filter->entityType,
    'request_id' => $filter->requestId,
    'source' => $filter->source,
    'date_from' => $filter->dateFrom,
    'date_to' => $filter->dateTo,
    'per_page' => $result->perPage,
];
require __DIR__ . '/../Layout/Partial/pagination.php';
?>
    </div>
</div>
