<?php

declare(strict_types=1);

use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Auth\Interface\Api\Response\AuthApiErrorFactory;
use App\Auth\Interface\Api\Response\AuthApiResponseFactory;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;

return [
    BearerTokenExtractor::class => BearerTokenExtractor::class,
    AuthApiResponseFactory::class => AuthApiResponseFactory::class,
    AuthApiErrorFactory::class => AuthApiErrorFactory::class,
    RedirectResponseFactory::class => RedirectResponseFactory::class,
];
