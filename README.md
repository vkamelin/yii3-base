# Yii3 Base — Модульное приложение на Yii 3

Модульный каркас приложения на Yii 3 для ERP, CRM, SaaS и внутренних инструментов.

## Особенности

- **Модульная архитектура** — DDD, Clean Architecture, Modular Monolith
- **Yii 3** — современный PHP-фреймворк
- **Docker** — контейнеризация для разработки и продакшена
- **MySQL + Redis** — основная БД и кэш/очереди
- **RBAC** — система управления доступом
- **REST API** — готовность к построению API
- **Очереди задач** — поддержка MySQL и Redis очередей через Supervisor
- **Инструменты качества кода** — Psalm, PHPStan, Rector, PHP CS Fixer

## Требования

- PHP 8.4+
- Docker и Docker Compose
- Composer

## Быстрый старт

### 1. Клонирование репозитория

```bash
git clone <repository-url>
cd <project-directory>
```

### 2. Настройка окружения

Скопируйте файл `.env.example` в `.env` и настройте переменные:

```bash
cp .env.example .env
```

### 3. Запуск среды разработки

```bash
make build    # Сборка Docker-образов
make up       # Запуск контейнеров
make migrate  # Применение миграций БД
```

### 4. Доступ к приложению

После запуска приложение доступно по адресу: `http://localhost`

## Основные команды Makefile

### Разработка

| Команда | Описание |
|---------|----------|
| `make build` | Сборка Docker-образов |
| `make up` | Запуск среды разработки |
| `make down` | Остановка среды разработки |
| `make clear` | Удаление контейнеров и томов |
| `make shell` | Вход в контейнер приложения |

### Инструменты

| Команда | Описание |
|---------|----------|
| `make yii [args]` | Выполнение Yii-команд |
| `make composer [args]` | Запуск Composer |
| `make migrate` | Применение миграций БД |
| `make queue` | Запуск воркера очередей |
| `make cs-fix` | Исправление кода через PHP CS Fixer |
| `make rector [args]` | Запуск Rector |

### Миграции

| Команда | Описание |
|---------|----------|
| `make migration-user [name]` | Создание миграции в модуле User |
| `make migration-auth [name]` | Создание миграции в модуле Auth |
| `make migration-rbac [name]` | Создание миграции в модуле RBAC |
| `make migration-shared [name]` | Создание миграции в Shared-модуле |

### Тесты и анализ

| Команда | Описание |
|---------|----------|
| `make test` | Запуск тестов (Codeception) |
| `make test-coverage` | Тесты с отчётом о покрытии |
| `make psalm` | Статический анализ Psalm |
| `make composer-dependency-analyser` | Анализ зависимостей Composer |

### Продакшен

| Команда | Описание |
|---------|----------|
| `make prod-build` | Сборка продакшен-образа |
| `make prod-push` | Пуш образа в реестр |
| `make prod-deploy` | Деплой в Docker Swarm |

## Структура проекта

```
├── config/                 # Конфигурация приложения
├── docker/                 # Docker-конфигурация
│   ├── compose.yml         # Основной compose-файл
│   ├── dev/                # Конфигурация для разработки
│   ├── prod/               # Конфигурация для продакшена
│   └── supervisor/         # Конфигурация Supervisor
├── docs/                   # Документация
├── public/                 # Публичная директория (entry point)
├── runtime/                # Рантайм-данные (логи, кэш)
├── src/                    # Исходный код
│   ├── Api/                # API-модуль
│   ├── Auth/               # Модуль аутентификации
│   ├── Console/            # Консольные команды
│   ├── Dashboard/          # Дашборд
│   ├── Rbac/               # Модуль RBAC
│   ├── Shared/             # Общие компоненты
│   └── User/               # Модуль пользователей
├── tests/                  # Тесты
├── assets/                 # Ассеты (CSS, JS)
└── vendor/                 # Зависимости Composer
```

## Модули

Приложение следует модульной архитектуре. Каждый модуль содержит:

- `Application/` — обработчики команд, запросов, джобы
- `Domain/` — доменные модели и логика
- `Infrastructure/` — репозитории, миграции, интеграции

### Доступные модули

- **User** — управление пользователями
- **Auth** — аутентификация и авторизация
- **Rbac** — роли и разрешения
- **Dashboard** — панель управления
- **Api** — REST API
- **Shared** — общие компоненты и инфраструктура

## Очереди задач

Приложение поддерживает очереди задач через MySQL и Redis.

### Создание джоба

```php
<?php
namespace App\User\Application\Queue;

use App\Shared\Infrastructure\Queue\JobInterface;

final readonly class SendWelcomeEmailJob implements JobInterface
{
    public function __construct(
        private string $userId,
        private string $email,
    ) {}

    public static function fromPayload(array $payload): static
    {
        return new self($payload['userId'], $payload['email']);
    }

    public function type(): string
    {
        return 'user.send_welcome_email';
    }

    public function toPayload(): array
    {
        return ['userId' => $this->userId, 'email' => $this->email];
    }
}
```

### Регистрация джоба

В `config/common/params.php`:

```php
'queue' => [
    'jobs' => [
        'user.send_welcome_email' => [
            'job' => \App\User\Application\Queue\SendWelcomeEmailJob::class,
            'handler' => \App\User\Application\Queue\SendWelcomeEmailHandler::class,
        ],
    ],
],
```

### Запуск воркера

```bash
make queue
```

Подробнее см. [docs/queue.md](docs/queue.md).

## Тестирование

```bash
# Запустить все тесты
make test

# Запустить unit-тесты
make test codecept run Unit

# Запустить functional-тесты
make test codecept run Functional
```

## Анализ кода

```bash
# Psalm
make psalm

# PHP CS Fixer (проверка)
make cs

# PHP CS Fixer (исправление)
make cs-fix

# Rector
make rector

# Анализ зависимостей
make composer-dependency-analyser
```

## API Документация

Полная OpenAPI/Swagger спецификация доступна в [docs/api/](docs/api/).

### Быстрый доступ

- **Swagger UI**: Откройте `docs/api/index.html` в браузере
- **OpenAPI Spec**: [docs/api/openapi.yaml](docs/api/openapi.yaml)
- **API README**: [docs/api/README.md](docs/api/README.md)

### Запуск Swagger UI

```bash
# Через PHP встроенный сервер
cd docs/api
php -S localhost:8080

# Затем откройте: http://localhost:8080
```

### API Endpoints

| Module   | Endpoints                          |
|----------|------------------------------------|
| Auth     | `/api/v1/auth/login`, `/logout`, `/me` |
| Users    | `/api/v1/users` (CRUD)             |
| Roles    | `/api/v1/roles`                    |
| Permissions | `/api/v1/permissions`           |

Подробнее см. [docs/api/README.md](docs/api/README.md).

## Переменные окружения

Основные переменные для настройки в `.env`:

| Переменная | Описание | По умолчанию |
|------------|----------|--------------|
| `APP_ENV` | Окружение (dev/prod) | `dev` |
| `APP_DEBUG` | Режим отладки | `true` |
| `DB_HOST` | Хост БД | `mysql` |
| `DB_PORT` | Порт БД | `3306` |
| `DB_NAME` | Имя БД | `app` |
| `DB_USER` | Пользователь БД | `app` |
| `DB_PASSWORD` | Пароль БД | `app` |
| `REDIS_HOST` | Хост Redis | `redis` |
| `REDIS_PORT` | Порт Redis | `6379` |
| `REDIS_PASSWORD` | Пароль Redis | `redis` |

## Лицензия

Проприетарная лицензия.