# Архитектура подключения модулей/доменов через файл конфигурации

## Анализ текущей архитектуры

Ваш проект использует **Yii3** с **модульной DDD-архитектурой**:
- Каждый домен лежит в `/src/{DomainName}/` (User, Auth, Rbac, Dashboard, Public, Api)
- Внутри домена: `Application/`, `Domain/`, `Infrastructure/`, `Interface/`
- Конфигурация собирается через `yiisoft/config` plugin из файлов в `/config/`
- Маршруты, DI-контейнер, команды консоли — всё разнесено по отдельным файлам

---

## Предлагаемое решение: Единый файл конфигурации модуля

### 1. Структура файла конфигурации модуля

Создайте файл `/config/modules.php` (или `/config/domains.php`):

```php
<?php

declare(strict_types=1);

return [
    // Список подключенных модулей
    'modules' => [
        'user' => [
            'class' => \App\User\Module::class,
            'enabled' => true,
            'priority' => 100,
        ],
        'auth' => [
            'class' => \App\Auth\Module::class,
            'enabled' => true,
            'priority' => 90,
        ],
        'rbac' => [
            'class' => \App\Rbac\Module::class,
            'enabled' => true,
            'priority' => 80,
        ],
        'dashboard' => [
            'class' => \App\Dashboard\Module::class,
            'enabled' => true,
            'priority' => 50,
        ],
        'blog' => [
            'class' => \App\Blog\Module::class,
            'enabled' => false, // Отключен по умолчанию
            'priority' => 40,
        ],
        // Новый модуль добавляется ОДНОЙ строкой:
        'products' => [
            'class' => \App\Products\Module::class,
            'enabled' => true,
            'priority' => 30,
        ],
    ],
];
```

---

### 2. Класс модуля (Module.php в каждом домене)

Каждый домен должен иметь класс `Module` с методами для регистрации компонентов:

```php
<?php

declare(strict_types=1);

namespace App\Products;

use Yiisoft\Definitions\Definition;

final class Module
{
    /**
     * Возвращает список DI-конфигураций модуля
     */
    public function getDiConfig(): array
    {
        return [
            __DIR__ . '/Infrastructure/di/*.php',
        ];
    }

    /**
     * Возвращает маршруты для веб-интерфейса
     */
    public function getWebRoutes(): array
    {
        return [
            __DIR__ . '/Interface/Web/routes.php',
        ];
    }

    /**
     * Возвращает маршруты для API
     */
    public function getApiRoutes(): array
    {
        return [
            __DIR__ . '/Interface/Api/routes.php',
        ];
    }

    /**
     * Возвращает консольные команды
     */
    public function getConsoleCommands(): array
    {
        return [
            __DIR__ . '/Console/*.php',
        ];
    }

    /**
     * Возвращает миграции
     */
    public function getMigrations(): array
    {
        return [
            __DIR__ . '/Infrastructure/Migration/*.php',
        ];
    }

    /**
     * Возвращает сидеры
     */
    public function getSeeders(): array
    {
        return [
            __DIR__ . '/Infrastructure/Seeder/*.php',
        ];
    }

    /**
     * Возвращает параметры модуля
     */
    public function getParams(): array
    {
        return [
            __DIR__ . '/config/params.php',
        ];
    }

    /**
     * Метод инициализации (вызывается при bootstrapping)
     */
    public function bootstrap(): void
    {
        // Дополнительная логика инициализации
    }
}
```

---

### 3. Обновление configuration.php

Модифицируйте `/config/configuration.php` для автоматического сбора конфигов из модулей:

```php
<?php

declare(strict_types=1);

use App\Environment;

$moduleConfig = require __DIR__ . '/modules.php';
$enabledModules = array_filter(
    $moduleConfig['modules'],
    fn($module) => $module['enabled'] === true
);

// Сортировка по приоритету
uasort($enabledModules, fn($a, $b) => $b['priority'] <=> $a['priority']);

// Сборка списков файлов из всех модулей
$diFiles = ['common/di/*.php'];
$diWebFiles = ['web/di/*.php'];
$diConsoleFiles = ['console/di/*.php'];
$routesFiles = ['common/routes.php'];
$webRoutesFiles = ['web/routes/*.php'];
$apiRoutesFiles = [];
$consoleCommands = ['console/commands.php'];
$migrations = [];
$seeders = [];
$paramsFiles = ['common/params.php'];
$bootstrapClasses = ['common/bootstrap.php'];

foreach ($enabledModules as $moduleName => $moduleConfig) {
    /** @var \App\Shared\ModuleInterface $module */
    $module = new ($moduleConfig['class'])();

    $diFiles = [...$diFiles, ...$module->getDiConfig()];
    $diWebFiles = [...$diWebFiles, ...$module->getDiConfig()]; // или отдельный метод
    $diConsoleFiles = [...$diConsoleFiles, ...$module->getDiConfig()];
    $webRoutesFiles = [...$webRoutesFiles, ...$module->getWebRoutes()];
    $apiRoutesFiles = [...$apiRoutesFiles, ...$module->getApiRoutes()];
    $consoleCommands = [...$consoleCommands, ...$module->getConsoleCommands()];
    $migrations = [...$migrations, ...$module->getMigrations()];
    $seeders = [...$seeders, ...$module->getSeeders()];
    $paramsFiles = [...$paramsFiles, ...$module->getParams()];

    if (method_exists($module, 'bootstrap')) {
        $bootstrapClasses[] = $moduleConfig['class'];
    }
}

return [
    'config-plugin' => [
        'params' => $paramsFiles,
        'params-web' => ['$params', 'web/params.php'],
        'params-console' => ['$params', 'console/params.php'],

        'di' => $diFiles,
        'di-web' => ['$di', ...$diWebFiles],
        'di-console' => ['$di', ...$diConsoleFiles],

        'routes' => $routesFiles,
        'routes-web' => ['$routes', ...$webRoutesFiles],
        'routes-api' => ['$routes', ...$apiRoutesFiles],

        'commands' => $consoleCommands,

        'migrations' => $migrations,
        'seeders' => $seeders,

        'bootstrap' => $bootstrapClasses,
        'bootstrap-web' => '$bootstrap',
        'bootstrap-console' => '$bootstrap',
    ],

    // ... остальная конфигурация environments
];
```

---

### 4. Альтернативный подход: Директория modules/

Более простой вариант — создать директорию `/config/modules/` с файлами для каждого модуля:

```
/config/modules/
├── user.php
├── auth.php
├── rbac.php
├── dashboard.php
└── products.php (новый модуль)
```

**Пример файла `/config/modules/products.php`:**

```php
<?php

declare(strict_types=1);

return [
    'enabled' => true,

    // DI контейнер
    'di' => [
        __DIR__ . '/../../src/Products/Infrastructure/di/services.php',
        __DIR__ . '/../../src/Products/Infrastructure/di/repositories.php',
    ],

    // Веб маршруты
    'web-routes' => [
        __DIR__ . '/../../src/Products/Interface/Web/routes.php',
    ],

    // API маршруты
    'api-routes' => [
        __DIR__ . '/../../src/Products/Interface/Api/routes.php',
    ],

    // Консольные команды
    'commands' => [
        'product:import' => \App\Products\Console\ImportCommand::class,
        'product:sync' => \App\Products\Console\SyncCommand::class,
    ],

    // Миграции
    'migrations' => [
        __DIR__ . '/../../src/Products/Infrastructure/Migration/*.php',
    ],

    // Сидеры
    'seeders' => [
        \App\Products\Infrastructure\Seeder\ProductSeeder::class,
    ],

    // Параметры
    'params' => [
        __DIR__ . '/../../src/Products/config/params.php',
    ],

    // Bootstrap классы
    'bootstrap' => [
        \App\Products\Bootstrap::class,
    ],
];
```

Затем в `configuration.php` собрать все включенные модули:

```php
$modulesDir = __DIR__ . '/modules';
$moduleConfigs = [];

foreach (glob($modulesDir . '/*.php') as $file) {
    $config = require $file;
    if ($config['enabled'] ?? false) {
        $moduleConfigs[] = $config;
    }
}

// Объединение всех конфигов
$allDi = array_merge(['common/di/*.php'], ...array_column($moduleConfigs, 'di'));
$allWebRoutes = array_merge(['web/routes/*.php'], ...array_column($moduleConfigs, 'web-routes'));
$allCommands = array_merge(['console/commands.php'], ...array_column($moduleConfigs, 'commands'));
// и т.д.
```

---

## План реализации

### Этап 1: Подготовка (1-2 часа)
1. ✅ Создать интерфейс `ModuleInterface` в `/src/Shared/`
2. ✅ Добавить класс `Module` в каждый существующий домен (User, Auth, Rbac, Dashboard, Public, Api)
3. ✅ Рефакторинг структуры папок внутри доменов под единый стандарт

### Этап 2: Создание системы конфигурации (2-3 часа)
4. ✅ Создать `/config/modules.php` с перечнем всех модулей
5. ✅ Модифицировать `/config/configuration.php` для чтения конфига модулей
6. ✅ Создать сборщик конфигов (ModuleConfigBuilder)

### Этап 3: Интеграция компонентов (3-4 часа)
7. ✅ Автоматическая регистрация DI-контейнеров из модулей
8. ✅ Автоматическая регистрация маршрутов (Web + API)
9. ✅ Автоматическая регистрация консольных команд
10. ✅ Автоматическое подключение миграций
11. ✅ Автоматическое подключение сидеров

### Этап 4: Тестирование (2-3 часа)
12. ✅ Протестировать включение/выключение модулей
13. ✅ Проверить порядок загрузки (priority)
14. ✅ Написать тесты для нового модуля "Products"

### Этап 5: Документация (1 час)
15. ✅ Создать шаблон нового модуля
16. ✅ Написать инструкцию "Как добавить новый модуль за 5 минут"

---

## Пример добавления нового модуля

После реализации достаточно:

**Шаг 1:** Создать структуру модуля:
```bash
mkdir -p src/Products/{Application,Domain,Infrastructure,Interface,Console,config}
```

**Шаг 2:** Добавить в `/config/modules.php`:
```php
'products' => [
    'class' => \App\Products\Module::class,
    'enabled' => true,
    'priority' => 30,
],
```

**Шаг 3:** Запустить rebuild:
```bash
composer yii-config-rebuild
```

**Всё!** Модуль подключен, доступны:
- ✅ Консольные команды
- ✅ Миграции
- ✅ Сидеры
- ✅ Dashboard (если есть Interface/Web)
- ✅ Public части
- ✅ API endpoints

---

## Преимущества подхода

| Преимущество | Описание |
|-------------|----------|
| 🚀 Быстрое подключение | 1 строка в конфиге |
| 🔌 Гибкость | Включение/выключение без удаления кода |
| 📦 Изоляция | Каждый модуль самодостаточен |
| 🎯 Приоритеты | Контроль порядка загрузки |
| 🔍 Прозрачность | Все модули видны в одном файле |
| 🧪 Тестируемость | Легко тестировать модули отдельно |

---

## Рекомендации

1. **Используйте priority** для контроля порядка загрузки (важно для зависимостей)
2. **Добавьте зависимости между модулями** в конфиг (например, `dependsOn => ['user', 'rbac']`)
3. **Создайте CLI команду** `php yii module:create products` для генерации структуры
4. **Добавьте кэширование** собранной конфигурации для production
5. **Реализуйте hot-reload** для development окружения

Этот подход позволит масштабировать приложение, добавляя новые домены минимальными усилиями.