<?php

declare(strict_types=1);

use App\Dashboard\Infrastructure\Asset\DashboardAsset;
use App\Shared\ApplicationParams;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Html\Html;

/**
 * @var string $content
 * @var ApplicationParams $applicationParams
 * @var Aliases $aliases
 * @var AssetManager $assetManager
 * @var Yiisoft\View\WebView $this
 */

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
    <title><?= Html::encode($this->getTitle()) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column">
<?php $this->beginBody() ?>
<div class="page page-center">
    <div class="container container-tight py-4">
        <?php require __DIR__ . '/Partial/flash-messages.php'; ?>
        <?= $content ?>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
