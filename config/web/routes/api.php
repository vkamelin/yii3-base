<?php

declare(strict_types=1);

use App\Api\Interface\Http\V1\Auth\LoginAction;
use App\Api\Interface\Http\V1\Auth\LogoutAction;
use App\Api\Interface\Http\V1\Auth\MeAction;
use App\Api\Interface\Http\V1\Rbac\CreateRoleAction;
use App\Api\Interface\Http\V1\Rbac\ListPermissionsAction;
use App\Api\Interface\Http\V1\Rbac\ListRolesAction;
use App\Api\Interface\Http\V1\User\CreateUserAction;
use App\Api\Interface\Http\V1\User\DeleteUserAction;
use App\Api\Interface\Http\V1\User\ListUsersAction;
use App\Api\Interface\Http\V1\User\UpdateUserAction;
use App\Api\Interface\Http\V1\User\ViewUserAction;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create('/api/v1')
        ->routes(
            Route::post('/auth/login')
                ->action(LoginAction::class)
                ->name('api.v1.auth.login'),
            Route::post('/auth/logout')
                ->action(LogoutAction::class)
                ->name('api.v1.auth.logout'),
            Route::get('/auth/me')
                ->action(MeAction::class)
                ->name('api.v1.auth.me'),

            Route::get('/users')
                ->action(ListUsersAction::class)
                ->name('api.v1.users.list'),
            Route::post('/users')
                ->action(CreateUserAction::class)
                ->name('api.v1.users.create'),
            Route::get('/users/{id}')
                ->action(ViewUserAction::class)
                ->name('api.v1.users.view'),
            Route::patch('/users/{id}')
                ->action(UpdateUserAction::class)
                ->name('api.v1.users.update'),
            Route::delete('/users/{id}')
                ->action(DeleteUserAction::class)
                ->name('api.v1.users.delete'),

            Route::get('/roles')
                ->action(ListRolesAction::class)
                ->name('api.v1.roles.list'),
            Route::post('/roles')
                ->action(CreateRoleAction::class)
                ->name('api.v1.roles.create'),
            Route::get('/permissions')
                ->action(ListPermissionsAction::class)
                ->name('api.v1.permissions.list'),
        ),
];
