<?php

declare(strict_types=1);

use App\Shared\Interface\Http\Middleware\CorsMiddleware;
use App\Shared\Interface\Http\Middleware\SelectiveCsrfTokenMiddleware;
use Yiisoft\Definitions\Reference;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Input\Http\HydratorAttributeParametersResolver;
use Yiisoft\Input\Http\RequestInputParametersResolver;
use Yiisoft\Middleware\Dispatcher\CompositeParametersResolver;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;

return [
    // Web middleware stack is configured here.
    //
    // Required future middleware:
    // - RequestIdMiddleware
    // - AccessLogMiddleware
    // - SessionAuthMiddleware
    // - RbacMiddleware
    // - RateLimitMiddleware
    MiddlewareDispatcher::class => [
        'class' => MiddlewareDispatcher::class,
        'withMiddlewares()' => [
            [
                ErrorCatcher::class,
                SessionMiddleware::class,
                SelectiveCsrfTokenMiddleware::class,
                RequestCatcherMiddleware::class,
                Router::class,
            ],
        ],
    ],

    ParametersResolverInterface::class => [
        'class' => CompositeParametersResolver::class,
        '__construct()' => [
            Reference::to(HydratorAttributeParametersResolver::class),
            Reference::to(RequestInputParametersResolver::class),
        ],
    ],

    CorsMiddleware::class => [
        'class' => CorsMiddleware::class,
        '__construct()' => [
            'allowedOrigins' => [
                'http://localhost',
                'http://localhost:5173',
            ],
            'allowCredentials' => true,
        ],
    ],
];
