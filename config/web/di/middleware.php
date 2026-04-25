<?php

declare(strict_types=1);

use App\Shared\Interface\Http\Middleware\CorsMiddleware;
use App\Shared\Interface\Http\Middleware\AccessLogMiddleware;
use App\Shared\Interface\Http\Middleware\ApiErrorMiddleware;
use App\Shared\Interface\Http\Middleware\AuthenticationMiddleware;
use App\Shared\Interface\Http\Middleware\JsonBodyParserMiddleware;
use App\Shared\Interface\Http\Middleware\RateLimitMiddleware;
use App\Shared\Interface\Http\Middleware\RequestIdMiddleware;
use App\Shared\Interface\Http\Middleware\SelectiveCsrfTokenMiddleware;
use App\Shared\Interface\Http\Middleware\WebErrorMiddleware;
use App\Rbac\Interface\Middleware\RbacMiddleware;
use Yiisoft\Definitions\Reference;
use Yiisoft\Input\Http\HydratorAttributeParametersResolver;
use Yiisoft\Input\Http\RequestInputParametersResolver;
use Yiisoft\Middleware\Dispatcher\CompositeParametersResolver;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;

return [
    MiddlewareDispatcher::class => [
        'class' => MiddlewareDispatcher::class,
        'withMiddlewares()' => [
            [
                ApiErrorMiddleware::class,
                WebErrorMiddleware::class,
                RequestIdMiddleware::class,
                AccessLogMiddleware::class,
                JsonBodyParserMiddleware::class,
                SessionMiddleware::class,
                SelectiveCsrfTokenMiddleware::class,
                RateLimitMiddleware::class,
                AuthenticationMiddleware::class,
                RbacMiddleware::class,
                RequestCatcherMiddleware::class,
                Router::class,
            ],
        ],
    ],

    ApiErrorMiddleware::class => [
        'class' => ApiErrorMiddleware::class,
        '__construct()' => [
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
        ],
    ],

    WebErrorMiddleware::class => [
        'class' => WebErrorMiddleware::class,
        '__construct()' => [
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
        ],
    ],

    JsonBodyParserMiddleware::class => [
        'class' => JsonBodyParserMiddleware::class,
        '__construct()' => [
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
        ],
    ],

    RateLimitMiddleware::class => [
        'class' => RateLimitMiddleware::class,
        '__construct()' => [
            'limit' => $params['middleware']['api']['rateLimit']['limit'] ?? 60,
            'windowSeconds' => $params['middleware']['api']['rateLimit']['windowSeconds'] ?? 60,
            'keyPrefix' => $params['middleware']['api']['rateLimit']['keyPrefix'] ?? 'rate_limit:api:',
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
        ],
    ],

    AuthenticationMiddleware::class => [
        'class' => AuthenticationMiddleware::class,
        '__construct()' => [
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
            'apiPublicPaths' => $params['middleware']['api']['publicPaths'] ?? ['/api/v1/auth/login'],
            'webProtectedPrefixes' => $params['middleware']['web']['protectedPrefixes'] ?? ['/dashboard'],
        ],
    ],

    RbacMiddleware::class => [
        'class' => RbacMiddleware::class,
        '__construct()' => [
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
            'webPermissionsByPrefix' => $params['middleware']['rbac']['webPermissionsByPrefix'] ?? ['/dashboard' => 'dashboard.view'],
            'apiPermissionsByPrefix' => $params['middleware']['rbac']['apiPermissionsByPrefix'] ?? [],
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
