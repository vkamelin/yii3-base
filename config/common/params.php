<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'Yii3 Base',
        'charset' => 'UTF-8',
        'locale' => $_ENV['APP_LOCALE'] ?? 'ru-RU',
        'runtimePath' => $_ENV['APP_RUNTIME_PATH'] ?? dirname(__DIR__, 2) . '/runtime',
    ],

    'db' => [
        'dsn' => sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'mysql',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'] ?? 'app',
        ),
        'username' => $_ENV['DB_USER'] ?? 'app',
        'password' => $_ENV['DB_PASSWORD'] ?? 'app',
    ],

    'redis' => [
        'host' => $_ENV['REDIS_HOST'] ?? 'redis',
        'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_PASSWORD'] ?? 'redis',
        'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
        'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 2.5),
    ],

    'queue' => [
        'defaultMaxAttempts' => 3,
        'redisKeyPrefix' => 'queue:default',
        'jobs' => [],
    ],

    'middleware' => [
        'api' => [
            'prefixes' => ['/api', '/api/'],
            'publicPaths' => ['/api/v1/auth/login'],
            'rateLimit' => [
                'limit' => (int) ($_ENV['API_RATE_LIMIT'] ?? 60),
                'windowSeconds' => (int) ($_ENV['API_RATE_LIMIT_WINDOW'] ?? 60),
                'keyPrefix' => $_ENV['API_RATE_LIMIT_PREFIX'] ?? 'rate_limit:api:',
            ],
        ],
        'web' => [
            'protectedPrefixes' => ['/dashboard'],
        ],
        'rbac' => [
            'webPermissionsByPrefix' => [
                '/dashboard' => 'dashboard.view',
            ],
            'apiPermissionsByPrefix' => [],
        ],
    ],

    'yiisoft/db-migration' => [
        'sourceNamespaces' => [
            'App\\User\\Infrastructure\\Migration',
            'App\\Auth\\Infrastructure\\Migration',
            'App\\Rbac\\Infrastructure\\Migration',
            'App\\Shared\\Infrastructure\\Migration',
        ],
        'newMigrationNamespace' => 'App\\Shared\\Infrastructure\\Migration',
    ],

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => null,
        'layout' => '@src/Public/Interface/Web/Shared/Layout/Main/layout.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],
];
