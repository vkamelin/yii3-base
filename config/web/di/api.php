<?php

declare(strict_types=1);

use App\Api\Interface\Http\Response\ApiErrorResponseFactory;
use App\Api\Interface\Http\Response\ApiResponseFactory;
use App\Auth\Interface\Api\BearerTokenExtractor;
use App\Auth\Interface\Web\Response\RedirectResponseFactory;

return [
    BearerTokenExtractor::class => BearerTokenExtractor::class,
    ApiResponseFactory::class => ApiResponseFactory::class,
    ApiErrorResponseFactory::class => ApiErrorResponseFactory::class,
    RedirectResponseFactory::class => RedirectResponseFactory::class,
];
