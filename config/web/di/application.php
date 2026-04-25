<?php

declare(strict_types=1);

use App\Public\Interface\Web\NotFound\NotFoundHandler;
use Yiisoft\Definitions\Reference;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Yii\Http\Application;

return [
    Application::class => [
        '__construct()' => [
            'dispatcher' => Reference::to(MiddlewareDispatcher::class),
            'fallbackHandler' => Reference::to(NotFoundHandler::class),
        ],
    ],
];
