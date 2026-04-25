# Queue (MySQL + Redis)

## Overview

- `MySqlQueue` is the default durable queue implementation (`QueueInterface` binding).
- `RedisQueue` is a transient fast queue implementation for non-critical workloads.
- Worker command: `queue:work`.

## 1. Create Job

```php
<?php

declare(strict_types=1);

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
        return new self(
            userId: (string) $payload['userId'],
            email: (string) $payload['email'],
        );
    }

    public function type(): string
    {
        return 'user.send_welcome_email';
    }

    public function toPayload(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
        ];
    }
}
```

## 2. Create Handler

```php
<?php

declare(strict_types=1);

namespace App\User\Application\Queue;

use App\Shared\Infrastructure\Queue\JobHandlerInterface;
use App\Shared\Infrastructure\Queue\JobInterface;

final readonly class SendWelcomeEmailHandler implements JobHandlerInterface
{
    public function handle(JobInterface $job): void
    {
        if (!$job instanceof SendWelcomeEmailJob) {
            throw new \RuntimeException('Unexpected job instance.');
        }

        // Infrastructure side effects only (mailer, API, etc.)
    }
}
```

## 3. Register Job In JobRegistry

Add registration in `config/common/params.php`:

```php
'queue' => [
    'defaultMaxAttempts' => 3,
    'redisKeyPrefix' => 'queue:default',
    'jobs' => [
        'user.send_welcome_email' => [
            'job' => \App\User\Application\Queue\SendWelcomeEmailJob::class,
            'handler' => \App\User\Application\Queue\SendWelcomeEmailHandler::class,
        ],
    ],
],
```

## 4. Push Job From Application Layer

```php
<?php

declare(strict_types=1);

namespace App\User\Application\Handler;

use App\Shared\Infrastructure\Queue\QueueInterface;
use App\User\Application\Queue\SendWelcomeEmailJob;

final readonly class RegisterUserHandler
{
    public function __construct(
        private QueueInterface $queue,
    ) {}

    public function handle(string $userId, string $email): void
    {
        $this->queue->push(new SendWelcomeEmailJob($userId, $email));
    }
}
```

## 5. Run Worker (Docker only)

```bash
make queue
```

or

```bash
docker compose -f docker/compose.yml -f docker/dev/compose.yml exec app php yii queue:work
```

With options:

```bash
docker compose -f docker/compose.yml -f docker/dev/compose.yml exec app php yii queue:work --sleep=3 --max-jobs=100 --max-time=3600 --memory=256 --queue=mysql
```

## 6. Migration (Docker only)

```bash
make migrate
```

or

```bash
docker compose -f docker/compose.yml -f docker/dev/compose.yml exec app php yii migrate:up
```

## 7. Supervisor Integration

Example config is available at `docker/supervisor/queue-worker.conf`:

```ini
command=php /app/yii queue:work --sleep=3 --queue=mysql
directory=/app
```

For container runtime, current active workers config is `docker/supervisor/conf.d/workers.conf`, which reads:

- `QUEUE_WORKER_1_COMMAND`
- `QUEUE_WORKER_2_COMMAND`

Default values in compose already point to `/app/yii queue:work`.

