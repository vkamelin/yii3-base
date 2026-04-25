<?php

declare(strict_types=1);

use App\Console;
use App\Shared\Infrastructure\Queue\Console\QueueWorkCommand;

return [
    'hello' => Console\HelloCommand::class,
    'seed:run' => Console\SeedCommand::class,
    'queue:work' => QueueWorkCommand::class,
];
