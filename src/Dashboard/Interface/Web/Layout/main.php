<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Html\Html;
use Yiisoft\Router\CurrentRoute;

/**
 * @var string $content
 * @var ApplicationParams $applicationParams
 * @var Aliases $aliases
 * @var CurrentRoute $currentRoute
 * @var string|null $csrf
 * @var Yiisoft\View\WebView $this
 */

$routeName = $currentRoute->getName() ?? '';

$isActive = static function (string $prefix) use ($routeName): bool {
    return str_starts_with($routeName, $prefix);
};

$menu = [
    ['label' => 'Home', 'url' => '/dashboard', 'prefix' => 'dashboard.home'],
    ['label' => 'Users', 'url' => '/dashboard/users', 'prefix' => 'dashboard.users'],
    ['label' => 'Roles', 'url' => '/dashboard/roles', 'prefix' => 'dashboard.roles'],
    ['label' => 'Permissions', 'url' => '/dashboard/permissions', 'prefix' => 'dashboard.permissions'],
];

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/dashboard/tabler.min.css">
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="page">
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <h1 class="navbar-brand navbar-brand-autodark"><?= Html::encode($applicationParams->name) ?></h1>
            <div class="collapse navbar-collapse show">
                <ul class="navbar-nav pt-lg-3">
                    <?php foreach ($menu as $item): ?>
                        <li class="nav-item <?= $isActive($item['prefix']) ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= Html::encode($item['url']) ?>">
                                <span class="nav-link-title"><?= Html::encode($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </aside>

    <div class="page-wrapper">
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item">
                        <form method="post" action="/dashboard/logout">
                            <input type="hidden" name="_csrf" value="<?= Html::encode((string) $csrf) ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        <div class="page-body">
            <div class="container-xl">
                <?php require __DIR__ . '/Partial/flash-messages.php'; ?>
                <?= $content ?>
            </div>
        </div>
    </div>
</div>
<script src="/assets/dashboard/tabler.min.js"></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

