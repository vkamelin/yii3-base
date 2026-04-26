<?php

declare(strict_types=1);

use App\Shared\Interface\Http\Middleware\CorsMiddleware;
use App\Shared\Interface\Http\Middleware\AccessLogMiddleware;
use App\Api\Interface\Http\Middleware\ApiErrorMiddleware;
use App\Shared\Interface\Http\Middleware\AuthenticationMiddleware;
use App\Shared\Application\Audit\ActivityLoggerInterface;
use App\Api\Interface\Http\Middleware\JsonResponseMiddleware;
use App\Shared\Interface\Http\Middleware\RateLimitMiddleware;
use App\Shared\Infrastructure\Audit\RequestAuditContext;
use App\Shared\Interface\Http\Middleware\RequestIdMiddleware;
use App\Shared\Interface\Http\Middleware\SelectiveCsrfTokenMiddleware;
use App\Shared\Interface\Http\Middleware\WebErrorMiddleware;
use App\Api\Interface\Http\Middleware\BearerTokenMiddleware;
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
                JsonResponseMiddleware::class,
                SessionMiddleware::class,
                SelectiveCsrfTokenMiddleware::class,
                RateLimitMiddleware::class,
                AuthenticationMiddleware::class,
                BearerTokenMiddleware::class,
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

    JsonResponseMiddleware::class => [
        'class' => JsonResponseMiddleware::class,
        '__construct()' => [
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
        ],
    ],

    RateLimitMiddleware::class => [
        'class' => RateLimitMiddleware::class,
        '__construct()' => [
            'activityLogger' => Reference::to(ActivityLoggerInterface::class),
            'auditContext' => Reference::to(RequestAuditContext::class),
            'limit' => $params['middleware']['api']['rateLimit']['limit'] ?? 60,
            'windowSeconds' => $params['middleware']['api']['rateLimit']['windowSeconds'] ?? 60,
            'keyPrefix' => $params['middleware']['api']['rateLimit']['keyPrefix'] ?? 'rate_limit:api:',
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
        ],
    ],

    AuthenticationMiddleware::class => [
        'class' => AuthenticationMiddleware::class,
        '__construct()' => [
            'activityLogger' => Reference::to(ActivityLoggerInterface::class),
            'auditContext' => Reference::to(RequestAuditContext::class),
            'apiPrefixes' => [],
            'apiPublicPaths' => [],
            'webProtectedPrefixes' => $params['middleware']['web']['protectedPrefixes'] ?? ['/dashboard'],
            'webPublicPaths' => $params['middleware']['web']['publicPaths'] ?? ['/login', '/dashboard/login'],
        ],
    ],

    BearerTokenMiddleware::class => [
        'class' => BearerTokenMiddleware::class,
        '__construct()' => [
            'activityLogger' => Reference::to(ActivityLoggerInterface::class),
            'auditContext' => Reference::to(RequestAuditContext::class),
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
            'publicPaths' => $params['middleware']['api']['publicPaths'] ?? ['/api/v1/auth/login'],
        ],
    ],

    RbacMiddleware::class => [
        'class' => RbacMiddleware::class,
        '__construct()' => [
            'activityLogger' => Reference::to(ActivityLoggerInterface::class),
            'auditContext' => Reference::to(RequestAuditContext::class),
            'apiPrefixes' => $params['middleware']['api']['prefixes'] ?? ['/api', '/api/'],
            'webPublicPaths' => $params['middleware']['rbac']['webPublicPaths'] ?? ['/login', '/dashboard/login'],
            'webPermissionsByPrefix' => $params['middleware']['rbac']['webPermissionsByPrefix'] ?? ['/dashboard' => 'dashboard.access'],
            'apiPermissionsByPrefix' => $params['middleware']['rbac']['apiPermissionsByPrefix'] ?? [],
            'apiPermissionsByMethodAndPrefix' => $params['middleware']['rbac']['apiPermissionsByMethodAndPrefix'] ?? [],
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
