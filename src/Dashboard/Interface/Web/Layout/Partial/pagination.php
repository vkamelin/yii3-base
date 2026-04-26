<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var int $page
 * @var int $perPage
 * @var int $total
 * @var string $path
 * @var array<string, scalar|null> $query
 */

$totalPages = (int) ceil($total / max(1, $perPage));
if ($totalPages <= 1) {
    return;
}

$buildUrl = static function (int $targetPage) use ($path, $query): string {
    $params = $query;
    $params['page'] = $targetPage;

    $parts = [];
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
    }

    return $parts === [] ? $path : $path . '?' . implode('&', $parts);
};
?>
<div class="card-footer d-flex align-items-center">
    <p class="m-0 text-secondary">Total: <?= Html::encode((string) $total) ?></p>
    <ul class="pagination m-0 ms-auto">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= Html::encode($buildUrl(max(1, $page - 1))) ?>">Prev</a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= Html::encode($buildUrl($i)) ?>"><?= Html::encode((string) $i) ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= Html::encode($buildUrl(min($totalPages, $page + 1))) ?>">Next</a>
        </li>
    </ul>
</div>

