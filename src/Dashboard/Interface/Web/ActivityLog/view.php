<?php

declare(strict_types=1);

use App\Shared\Application\Audit\Query\ActivityLogView;
use Yiisoft\Html\Html;

use function json_encode;

/**
 * @var ActivityLogView $item
 */
?>
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Activity Event</h3>
                <a class="btn btn-sm btn-outline-secondary" href="/dashboard/activity-log">Back</a>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">ID</dt>
                    <dd class="col-sm-9"><code><?= Html::encode($item->id) ?></code></dd>

                    <dt class="col-sm-3">Created At</dt>
                    <dd class="col-sm-9"><?= Html::encode($item->createdAt) ?></dd>

                    <dt class="col-sm-3">Action</dt>
                    <dd class="col-sm-9"><span class="badge text-bg-primary"><?= Html::encode($item->action) ?></span></dd>

                    <dt class="col-sm-3">Source</dt>
                    <dd class="col-sm-9"><span class="badge text-bg-secondary"><?= Html::encode($item->source) ?></span></dd>

                    <dt class="col-sm-3">Actor Type</dt>
                    <dd class="col-sm-9"><?= Html::encode($item->actorType) ?></dd>

                    <dt class="col-sm-3">Actor User ID</dt>
                    <dd class="col-sm-9"><?= Html::encode($item->actorUserId ?? '-') ?></dd>

                    <dt class="col-sm-3">Entity</dt>
                    <dd class="col-sm-9"><?= Html::encode(($item->entityType ?? '-') . ($item->entityId !== null ? ' #' . $item->entityId : '')) ?></dd>

                    <dt class="col-sm-3">Request ID</dt>
                    <dd class="col-sm-9"><?= Html::encode($item->requestId ?? '-') ?></dd>

                    <dt class="col-sm-3">IP</dt>
                    <dd class="col-sm-9"><?= Html::encode($item->ip ?? '-') ?></dd>

                    <dt class="col-sm-3">User Agent</dt>
                    <dd class="col-sm-9 text-break"><?= Html::encode($item->userAgent ?? '-') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Payload</h3>
            </div>
            <div class="card-body">
                <pre class="mb-0"><code><?= Html::encode((string) json_encode($item->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></code></pre>
            </div>
        </div>
    </div>
</div>
