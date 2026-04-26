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

$menu = [
    ['label' => 'Пользователи', 'url' => '/dashboard/users', 'prefix' => 'dashboard.users', 'icon' => 'users'],
    ['label' => 'Роли', 'url' => '/dashboard/roles', 'prefix' => 'dashboard.roles', 'icon' => 'user-shield'],
    ['label' => 'Разрешения', 'url' => '/dashboard/permissions', 'prefix' => 'dashboard.permissions', 'icon' => 'user-key'],
];

$assetManager->register(DashboardAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="/fonts/inter/inter.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
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
            <div class="collapse navbar-collapse d-none d-lg-flex">
                <ul class="navbar-nav pt-lg-3">
                    <?php foreach ($menu as $item): ?>
                        <li class="nav-item <?= $isActive($item['prefix']) ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= Html::encode($item['url']) ?>">
                                <?php if (!empty($item['icon'])): ?>
                                    <span class="nav-link-icon"><i class="ti ti-<?= $item['icon'] ?>"></i></span>
                                <?php endif; ?>
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

<div class="offcanvas offcanvas-start offcanvas-narrow" tabindex="-1" id="offcanvasSidebar" aria-modal="true" role="dialog" data-bs-theme="dark">
  <div class="offcanvas-header">
    <h2 class="offcanvas-title"><?= Html::encode($applicationParams->name) ?></h2>
    <button type="button" class="btn-close h3" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- body -->
     <div class="collapse navbar-collapse show">
                <ul class="navbar-nav pt-lg-3">
                    <?php foreach ($menu as $item): ?>
                        <li class="nav-item <?= $isActive($item['prefix']) ? 'active' : '' ?>">
                            <a class="nav-link" href="<?= Html::encode($item['url']) ?>">
                                <?php if (!empty($item['icon'])): ?>
                                    <span class="nav-link-icon"><i class="ti ti-<?= $item['icon'] ?>"></i></span>
                                <?php endif; ?>
                                <span class="nav-link-title"><?= Html::encode($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
  </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
