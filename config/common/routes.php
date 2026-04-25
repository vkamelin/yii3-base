<?php

declare(strict_types=1);

use App\Auth\Interface\Api\V1\LoginAction as ApiLoginAction;
use App\Auth\Interface\Api\V1\LogoutAction as ApiLogoutAction;
use App\Auth\Interface\Api\V1\MeAction as ApiMeAction;
use App\Auth\Interface\Web\Login\LoginPageAction;
use App\Auth\Interface\Web\Login\LoginSubmitAction;
use App\Auth\Interface\Web\Logout\LogoutAction;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')
                ->action(App\Public\Interface\Web\HomePage\Action::class)
                ->name('home'),
            Route::get('/login')
                ->action(LoginPageAction::class)
                ->name('auth.login'),
            Route::post('/login')
                ->action(LoginSubmitAction::class)
                ->name('auth.login.submit'),
            Route::post('/logout')
                ->action(LogoutAction::class)
                ->name('auth.logout'),
            Group::create('/api/v1')
                ->routes(
                    Route::post('/auth/login')
                        ->action(ApiLoginAction::class)
                        ->name('api.v1.auth.login'),
                    Route::post('/auth/logout')
                        ->action(ApiLogoutAction::class)
                        ->name('api.v1.auth.logout'),
                    Route::get('/auth/me')
                        ->action(ApiMeAction::class)
                        ->name('api.v1.auth.me'),
                ),
        ),
];
