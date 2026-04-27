# API Documentation

OpenAPI 3.1.0 спецификация для Enterprise Application API.

## Структура

```
docs/api/
├── openapi.yaml      # Основная OpenAPI спецификация
├── index.html        # Swagger UI интерфейс
└── README.md         # Эта документация
```

## Быстрый старт

### Запуск Swagger UI локально

#### Вариант 1: Через PHP встроенный сервер

```bash
cd docs/api
php -S localhost:8080
```

Откройте браузер: http://localhost:8080

#### Вариант 2: Через Docker

```bash
docker run --rm -it -p 8080:8080 \
  -v $(pwd)/docs/api:/usr/share/nginx/html \
  nginx:alpine
```

#### Вариант 3: Через VS Code Live Server

Установите расширение "Live Server" и откройте `index.html`

### Импорт в инструменты

- **Postman**: File → Import → Загрузите `openapi.yaml`
- **Insomnia**: Import → Загрузите `openapi.yaml`
- **Stoplight**: Import OpenAPI → Загрузите `openapi.yaml`

## API Endpoints

### Authentication

| Method | Endpoint      | Описание                    | Auth |
|--------|---------------|-----------------------------|------|
| POST   | `/auth/login` | Вход в систему              | ❌   |
| POST   | `/auth/logout`| Выход из системы            | ✅   |
| GET    | `/auth/me`    | Информация о текущем юзере  | ✅   |

### Users

| Method | Endpoint        | Описание                     | Auth |
|--------|-----------------|------------------------------|------|
| GET    | `/users`        | Список пользователей         | ✅   |
| POST   | `/users`        | Создание пользователя        | ✅   |
| GET    | `/users/{id}`   | Получение пользователя       | ✅   |
| PATCH  | `/users/{id}`   | Обновление пользователя      | ✅   |
| DELETE | `/users/{id}`   | Блокировка пользователя      | ✅   |

### Roles (RBAC)

| Method | Endpoint   | Описание         | Auth |
|--------|------------|------------------|------|
| GET    | `/roles`   | Список ролей     | ✅   |
| POST   | `/roles`   | Создание роли    | ✅   |

### Permissions (RBAC)

| Method | Endpoint         | Описание           | Auth |
|--------|------------------|--------------------|------|
| GET    | `/permissions`   | Список прав        | ✅   |

## Аутентификация

API использует Bearer token аутентификацию.

### Получение токена

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

Ответ:

```json
{
  "data": {
    "user": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "email": "user@example.com",
      "name": "John Doe",
      "status": "active"
    },
    "token": {
      "type": "Bearer",
      "value": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "expires_at": "2025-12-31T23:59:59Z"
    }
  },
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Использование токена

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

## Формат ответов

### Успешный ответ

```json
{
  "data": { ... },
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Пагинированный ответ

```json
{
  "data": [...],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 100
  },
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Ответ об ошибке

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed.",
    "details": {
      "email": ["Email is required."],
      "name": ["Name must be a non-empty string."]
    }
  },
  "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

## Коды ошибок

| Код               | HTTP Status | Описание                     |
|-------------------|-------------|------------------------------|
| `VALIDATION_ERROR`| 422         | Ошибка валидации             |
| `UNAUTHENTICATED` | 401         | Неверные или отсутствующие учётные данные |
| `FORBIDDEN`       | 403         | Недостаточно прав            |
| `NOT_FOUND`       | 404         | Ресурс не найден             |
| `CONFLICT`        | 409         | Конфликт (дубликат)          |
| `INTERNAL_ERROR`  | 500         | Внутренняя ошибка сервера    |

## Заголовки ответов

| Заголовок            | Описание                           |
|----------------------|------------------------------------|
| `Content-Type`       | `application/json; charset=UTF-8`  |
| `X-Request-Id`       | Уникальный ID запроса              |
| `X-Correlation-Id`   | ID для трассировки между сервисами |

## Параметры запроса

### Общие query параметры

- `page` (integer, default: 1) - Номер страницы
- `per_page` (integer, default: 20, max: 100) - Количество элементов на странице
- `search` (string) - Строка поиска

### Статусы пользователя

- `active` - Активный
- `inactive` - Неактивный
- `blocked` - Заблокированный

## Примеры запросов

### Создание пользователя

```bash
curl -X POST http://localhost:8000/api/v1/users \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "email": "newuser@example.com",
    "name": "Jane Doe"
  }'
```

### Получение списка пользователей с фильтрацией

```bash
curl -X GET "http://localhost:8000/api/v1/users?page=1&per_page=10&search=john&status=active" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Обновление пользователя

```bash
curl -X PATCH http://localhost:8000/api/v1/users/550e8400-e29b-41d4-a716-446655440000 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "John Updated",
    "status": "inactive"
  }'
```

### Создание роли

```bash
curl -X POST http://localhost:8000/api/v1/roles \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "code": "manager",
    "name": "Менеджер",
    "description": "Роль менеджера",
    "is_system": false
  }'
```

## Тестирование

### Запуск тестов API

```bash
# Unit тесты
vendor/bin/phpunit tests/Unit/Api/

# Functional тесты
vendor/bin/codecept run functional ApiV1Cest

# Web тесты
vendor/bin/codecept run web
```

## Генерация кода

### Генерация клиентского кода

Используйте [OpenAPI Generator](https://openapi-generator.tech/):

```bash
# PHP клиент
openapi-generator generate \
  -i docs/api/openapi.yaml \
  -g php \
  -o clients/php

# TypeScript клиент
openapi-generator generate \
  -i docs/api/openapi.yaml \
  -g typescript-axios \
  -o clients/typescript

# Python клиент
openapi-generator generate \
  -i docs/api/openapi.yaml \
  -g python \
  -o clients/python
```

## Обновление спецификации

При добавлении новых эндпоинтов обновите:

1. `openapi.yaml` - основная спецификация
2. Добавьте примеры в соответствующие секции
3. Обновите этот README если добавлены новые параметры

## Валидация спецификации

```bash
# Установка validator
npm install -g @redocly/cli

# Валидация
openapi lint docs/api/openapi.yaml
```

## Ссылки

- [OpenAPI Specification](https://swagger.io/specification/)
- [Swagger UI](https://github.com/swagger-api/swagger-ui)
- [Redoc](https://github.com/Redocly/redoc) - альтернативная документация
