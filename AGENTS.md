Ниже — production-ready документ `AGENTS.md` для твоего проекта. Он задаёт жёсткие рамки для агента и разработчика, устраняет двусмысленность и снижает риск архитектурной деградации.

---

# AGENTS.md

## 1. Project Overview

Это базовый application skeleton для построения корпоративных веб-приложений:

* ERP / CRM
* SaaS
* internal tools

Архитектура:

* DDD (Domain-Driven Design)
* Clean Architecture
* Modular Monolith (bounded contexts)
* Layered architecture

Слои:

* Domain — бизнес-логика
* Application — use-cases
* Infrastructure — реализации
* Interface — Web / API / Console

Каждый контекст изолирован и не зависит от реализации других контекстов 

Технологии:

* PHP 8.4
* Yii3
* MySQL 8 (основное хранилище)
* Redis (cache, sessions, queue)
* Supervisor (воркеры)
* Docker

---

## 2. Setup / Run

### Запуск проекта

```bash
make build
make up
```

### Установка зависимостей

```bash
composer install
```

### Миграции

```bash
make migrate
```

### Seed данные

```bash
make seed
```

### Очереди

```bash
make queue
```

### Тесты

```bash
vendor/bin/phpunit
```

---

## 3. Project Structure

```
src/
  Auth/
  User/
  Rbac/
  Dashboard/
  Public/
  Api/
  Shared/
  Console/
```

Каждый модуль:

```
Module/
  Domain/
  Application/
  Infrastructure/
```

---

## 4. Architecture Rules (CRITICAL)

### 4.1 Строгие ограничения слоёв

| Слой           | Может зависеть от |
| -------------- | ----------------- |
| Domain         | НИ ОТ КОГО        |
| Application    | Domain            |
| Infrastructure | Domain            |
| Interface      | Application       |

### 4.2 Запрещено

* ❌ Domain → Infrastructure
* ❌ Domain → Framework (Yii)
* ❌ Business logic в Controller
* ❌ ActiveRecord как доменная модель
* ❌ Service Locator / глобальные состояния
* ❌ Статические вызовы для бизнес-логики

---

## 5. Domain Rules

Domain — центр системы.

### Разрешено:

* Entities (immutable / controlled mutation)
* Value Objects
* Domain Services
* Repository interfaces
* Domain Events

### Запрещено:

* ORM
* HTTP
* Yii
* Redis / DB

### Пример:

```php
interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
}
```

Реализация — только в Infrastructure.

---

## 6. Application Layer

Ответственность:

* orchestration
* use-cases
* валидация
* DTO

### Запрещено:

* сложная бизнес-логика
* доступ к БД напрямую

---

## 7. Infrastructure

Содержит:

* MySQL (репозитории)
* Redis (cache, locks, rate limit)
* Queue
* External APIs

Кеш обязателен для read-heavy операций, т.к. снижает нагрузку на БД и latency 

---

## 8. Interface Layer

### Web / Dashboard

* Tabler UI (Bootstrap 5.3)
* Tabler Icons
* только presentation logic

### API

* REST
* /api/v1
* JSON only
* единый формат ошибок
* Bearer Token

---

## 9. Middleware

Обязательные:

* Auth
* RBAC
* Logging
* Rate limiting
* Request ID

---

## 10. Security Rules

Обязательно:

* CSRF
* Password hashing
* Input validation
* Output escaping
* Rate limiting

---

## 11. Database Rules

* MySQL = source of truth
* ACID транзакции обязательны для критичных операций 
* Индексы для всех частых запросов
* Избегать N+1

---

## 12. Performance Constraints

### Обязательно:

* Redis cache
* Pagination
* Lazy loading
* Batch processing

### Запрещено:

* heavy joins без необходимости
* full table scan в runtime

---

## 13. Testing

Типы:

* Unit (Domain)
* Functional (Application)
* API

### Требования:

* Domain покрывается unit тестами
* Use-cases покрываются обязательно

---

## 14. Code Style

### Обязательные правила:

* strict_types=1
* readonly где возможно
* dependency injection
* явные интерфейсы

### Naming:

* UserService → плохо
* CreateUserHandler → хорошо

---

## 15. Workflow (для агента)

Агент работает как инженер без контекста 

### ОБЯЗАТЕЛЬНЫЙ pipeline:

1. Понять задачу
2. Описать решение
3. Предложить альтернативы
4. Реализовать

```
1. Ты понимаешь задачу?
2. Это оптимально?
3. Какие альтернативы?
4. Реализуй
```

---

## 16. PR / Commits

### Commit format

```
feat(auth): add login handler
fix(user): correct email validation
refactor(rbac): extract permission service
```

---

## 17. Restrictions (CRITICAL)

### Нельзя менять без согласования:

* архитектуру слоёв
* границы контекстов
* Domain модели

### Нельзя:

* упрощать архитектуру
* добавлять “магические” решения
* смешивать слои

---

## 18. Agent Constraints

Агент НЕ должен:

* придумывать архитектуру заново
* игнорировать текущие паттерны
* изменять контракты без причины

Агент ДОЛЖЕН:

* работать в рамках структуры
* минимизировать изменения
* явно объяснять решения

---

## 19. Dashboard UI Rules

* Tabler UI (Bootstrap 5.3)
* компонентный подход
* таблицы:

  * фильтрация
  * сортировка
  * пагинация

Запрещено:

* inline JS логика
* смешивание backend логики

---

## 20. Final Principle

> Архитектура важнее скорости разработки

> Domain — главный источник истины

> Любое решение должно масштабироваться

---

Если хочешь, дальше могу:

* разложить это на реальные php namespace + composer config
* или сразу сгенерировать starter repo (docker + yii3 config + DI)
