<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;

/** @var array $params */

return [
    ApplicationParams::class => [
        '__construct()' => [
            'name' => $params['app']['name'],
            'charset' => $params['app']['charset'],
            'locale' => $params['app']['locale'],
        ],
    ],
];
