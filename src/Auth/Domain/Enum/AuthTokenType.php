<?php

declare(strict_types=1);

namespace App\Auth\Domain\Enum;

enum AuthTokenType: string
{
    case Api = 'api';
    case Remember = 'remember';
    case PasswordReset = 'password_reset';
    case EmailVerify = 'email_verify';
}
