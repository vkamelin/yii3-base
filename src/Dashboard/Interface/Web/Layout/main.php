<?php

declare(strict_types=1);

use App\Dashboard\Infrastructure\Asset\DashboardAsset;
use App\Shared\ApplicationParams;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Html\Html;
use Yiisoft\Router\CurrentRoute;

/**
 * @var string $content
 * @var ApplicationParams $applicationParams
 * @var Aliases $aliases
 * @var AssetManager $assetManager
 * @var CurrentRoute $currentRoute
 * @var string|null $csrf
 * @var Yiisoft\View\WebView $this
 */

$routeName = $currentRoute->getName() ?? '';

$isActive = static function (string $prefix) use ($routeName): bool {
    return str_starts_with($routeName, $prefix);
};

$hasActiveChild = static function (array $children) use ($isActive): bool {
    foreach ($children as $child) {
        $childPrefix = $child['prefix'] ?? null;
        if (is_string($childPrefix) && $isActive($childPrefix)) {
            return true;
        }
    }

    return false;
};

$menu = [
    ['label' => 'Управление доступом', 'icon' => 'shield', 'child' => [
        ['label' => 'Пользователи', 'url' => '/dashboard/users', 'prefix' => 'dashboard.users', 'icon' => 'users'],
        ['label' => 'Роли', 'url' => '/dashboard/roles', 'prefix' => 'dashboard.roles', 'icon' => 'user-shield'],
        ['label' => 'Разрешения', 'url' => '/dashboard/permissions', 'prefix' => 'dashboard.permissions', 'icon' => 'user-key'],
        ['label' => 'Activity log', 'url' => '/dashboard/activity-log', 'prefix' => 'dashboard.activity-log', 'icon' => 'history'],
    ]],
];

$renderMenu = static function (array $items, string $scope) use ($isActive, $hasActiveChild): void {
    $wrapperClass = $scope === 'sidebar'
        ? 'collapse navbar-collapse d-none d-lg-flex'
        : 'collapse navbar-collapse show';
    ?>
    <div class="<?= Html::encode($wrapperClass) ?>">
        <ul class="navbar-nav pt-lg-3 dashboard-menu">
            <?php foreach ($items as $item): ?>
                <?php
                $label = (string) ($item['label'] ?? '');
                $icon = (string) ($item['icon'] ?? 'circle');
                $children = is_array($item['child'] ?? null) ? $item['child'] : [];
                $isParentActive = $hasActiveChild($children);
                $collapseId = sprintf('%s-menu-%s', $scope, substr(md5($label . $icon), 0, 10));
                ?>
                <li class="nav-item">
                    <a
                        class="nav-link dashboard-menu-parent <?= $isParentActive ? 'active' : 'collapsed' ?>"
                        href="#<?= Html::encode($collapseId) ?>"
                        data-bs-toggle="collapse"
                        data-bs-auto-close="false"
                        role="button"
                        aria-expanded="<?= $isParentActive ? 'true' : 'false' ?>"
                        aria-controls="<?= Html::encode($collapseId) ?>"
                    >
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-<?= Html::encode($icon) ?>"></i>
                        </span>
                        <span class="nav-link-title"><?= Html::encode($label) ?></span>
                    </a>
                    <div class="collapse dashboard-submenu <?= $isParentActive ? 'show' : '' ?>" id="<?= Html::encode($collapseId) ?>">
                        <ul class="navbar-nav dashboard-submenu-list">
                            <?php foreach ($children as $child): ?>
                                <?php
                                $childLabel = (string) ($child['label'] ?? '');
                                $childUrl = (string) ($child['url'] ?? '#');
                                $childPrefix = (string) ($child['prefix'] ?? '');
                                $isChildActive = $childPrefix !== '' && $isActive($childPrefix);
                                $childIcon = (string) ($child['icon'] ?? 'chevron-right');
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link dashboard-submenu-link <?= $isChildActive ? 'active' : '' ?>" href="<?= Html::encode($childUrl) ?>">
                                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                                            <i class="ti ti-<?= Html::encode($childIcon) ?>"></i>
                                        </span>
                                        <span class="nav-link-title"><?= Html::encode($childLabel) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
};

$assetManager->register(DashboardAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());
$this->addCssStrings([
    <<<'CSS'
.dashboard-submenu-list {
    margin: 0;
    padding-left: 0;
}
.dashboard-submenu-link {
    justify-content: flex-start;
    text-align: left;
    color: inherit;
    opacity: .75;
    text-decoration: none;
    padding-left: 1rem;
}
.dashboard-submenu-link:hover,
.dashboard-submenu-link:focus {
    color: var(--tblr-navbar-link-hover-color);
    opacity: 1;
    text-decoration: none;
}
.dashboard-submenu-link.active {
    color: var(--tblr-navbar-link-active-color);
    opacity: 1;
}
CSS,
]);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="/fonts/inter/inter.css">
    <link rel="stylesheet" href="/fonts/tabler-icons/tabler-icons.min.css">
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="page">
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <div class="d-flex justify-content-start align-items-center w-100">
                <a href="#" class="p-2 m-0 h1 text-white d-block d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
                    <i class="ti ti-menu-2"></i>
                </a>
                <a href="/dashboard" class="navbar-brand navbar-brand-autodark h1 align-self-center ms-2">
                    <?= Html::encode($applicationParams->name) ?>
                </a>
            </div>
            <?php $renderMenu($menu, 'sidebar'); ?>
        </div>
    </aside>

    <div class="page-wrapper">
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-fluid">
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
            <div class="container-fluid">
                <?php require __DIR__ . '/Partial/flash-messages.php'; ?>
                <?= $content ?>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start offcanvas-narrow" tabindex="-1" id="offcanvasSidebar" aria-modal="true" role="dialog" data-bs-theme="dark">
  <div class="offcanvas-header">
    <h2 class="offcanvas-title"><?= Html::encode($applicationParams->name) ?></h2>
    <button type="button" class="btn-close h3" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <?php $renderMenu($menu, 'offcanvas'); ?>
  </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
