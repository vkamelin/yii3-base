<?php

declare(strict_types=1);

use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\MySqlUserRepository;
use App\User\Infrastructure\Persistence\UserHydrator;
use App\User\Infrastructure\Persistence\UserReadRepository;

return [
    UserRepositoryInterface::class => MySqlUserRepository::class,
    UserHydrator::class => UserHydrator::class,
    UserReadRepository::class => UserReadRepository::class,
];
