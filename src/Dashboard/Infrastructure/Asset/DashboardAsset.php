<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Asset;

use Yiisoft\Assets\AssetBundle;

final class DashboardAsset extends AssetBundle
{
    public ?string $basePath = '@assets/dashboard';
    public ?string $baseUrl = '@assetsUrl/dashboard';
    public ?string $sourcePath = '@assetsSource/dashboard';

    public array $css = [
        'tabler.min.css',
    ];

    public array $js = [
        'tabler.min.js',
    ];
}
