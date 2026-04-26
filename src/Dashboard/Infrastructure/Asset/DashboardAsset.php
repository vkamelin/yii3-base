<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Asset;

use Yiisoft\Assets\AssetBundle;

final class DashboardAsset extends AssetBundle
{
    public ?string $basePath = '@basePath';
    public ?string $baseUrl = '@baseUrl';

    public array $css = [
        '/fonts/inter/inter.css',
        '/assets/dashboard/tabler.min.css',
    ];

    public array $js = [
        '/assets/dashboard/tabler.min.js',
    ];
}

