<?php

declare(strict_types=1);

use App\Auth\Application\Session\AuthSessionInterface;
use App\Auth\Domain\Repository\AuthTokenRepositoryInterface;
use App\Auth\Domain\Repository\UserCredentialsRepositoryInterface;
use App\Auth\Domain\Service\PasswordHasherInterface;
use App\Auth\Domain\Service\TokenGeneratorInterface;
use App\Auth\Infrastructure\Persistence\AuthTokenHydrator;
use App\Auth\Infrastructure\Persistence\MySqlAuthTokenRepository;
use App\Auth\Infrastructure\Persistence\MySqlUserCredentialsRepository;
use App\Auth\Infrastructure\Security\RandomTokenGenerator;
use App\Auth\Infrastructure\Security\YiiPasswordHasher;
use App\Auth\Infrastructure\Session\YiiSessionAuthStorage;

return [
    PasswordHasherInterface::class => YiiPasswordHasher::class,
    TokenGeneratorInterface::class => RandomTokenGenerator::class,
    UserCredentialsRepositoryInterface::class => MySqlUserCredentialsRepository::class,
    AuthTokenRepositoryInterface::class => MySqlAuthTokenRepository::class,
    AuthSessionInterface::class => YiiSessionAuthStorage::class,
    AuthTokenHydrator::class => AuthTokenHydrator::class,
];
