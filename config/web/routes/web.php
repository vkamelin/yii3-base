<?php

declare(strict_types=1);

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
        ),
];
