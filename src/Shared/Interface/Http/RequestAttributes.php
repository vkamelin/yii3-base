<?php

declare(strict_types=1);

namespace App\Shared\Interface\Http;

final class RequestAttributes
{
    public const REQUEST_ID = 'request_id';
    public const CORRELATION_ID = 'correlation_id';
    public const USER_ID = 'authenticated_user_id';
    public const AUTH_CHANNEL = 'auth_channel';
    public const API_TOKEN = 'api_token';
    public const AUTH_RESULT = 'auth_result';

    private function __construct() {}
}
