<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Asset;

use Yiisoft\Assets\AssetBundle;

final class DatatablesAsset extends AssetBundle
{
    public ?string $basePath = '@assets/datatables';
    public ?string $baseUrl = '@assetsUrl/datatables';
    public ?string $sourcePath = '@assetsSource/datatables';

    public array $css = [
        'datatables.min.css',
    ];

    public array $js = [
        'pdfmake.min.js',
        'vfs_fonts.js',
        'datatables.min.js',
    ];
}
