<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    PdoDriverInterface::class => [
        'class' => Driver::class,
        '__construct()' => [
            'dsn' => sprintf(
                'mysql:host=%s;port=%d;dbname=%s',
                $params['database']['host'],
                $params['database']['port'],
                $params['database']['name'],
            ),
            'username' => $params['database']['username'],
            'password' => $params['database']['password'],
            'attributes' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        'charset()' => [$params['database']['charset']],
    ],

    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => Reference::to(PdoDriverInterface::class),
        ],
        'setTablePrefix()' => [$params['database']['tablePrefix']],
    ],
];
