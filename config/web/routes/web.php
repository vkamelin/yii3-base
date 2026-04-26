<?php

declare(strict_types=1);

use App\Auth\Interface\Web\Login\LoginPageAction;
use App\Auth\Interface\Web\Login\LoginSubmitAction;
use App\Auth\Interface\Web\Logout\LogoutAction;
use App\Dashboard\Interface\Web\Auth\LoginPageAction as DashboardLoginPageAction;
use App\Dashboard\Interface\Web\Auth\LoginSubmitAction as DashboardLoginSubmitAction;
use App\Dashboard\Interface\Web\Auth\LogoutAction as DashboardLogoutAction;
use App\Dashboard\Interface\Web\ActivityLog\IndexAction as DashboardActivityLogIndexAction;
use App\Dashboard\Interface\Web\ActivityLog\ViewAction as DashboardActivityLogViewAction;
use App\Dashboard\Interface\Web\Home\IndexAction as DashboardHomeAction;
use App\Dashboard\Interface\Web\Permission\ListAction as DashboardPermissionsAction;
use App\Dashboard\Interface\Web\Role\ListAction as DashboardRolesAction;
use App\Dashboard\Interface\Web\User\CreatePageAction as DashboardUserCreatePageAction;
use App\Dashboard\Interface\Web\User\CreateSubmitAction as DashboardUserCreateSubmitAction;
use App\Dashboard\Interface\Web\User\EditPageAction as DashboardUserEditPageAction;
use App\Dashboard\Interface\Web\User\EditSubmitAction as DashboardUserEditSubmitAction;
use App\Dashboard\Interface\Web\User\ListAction as DashboardUsersAction;
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
            Route::get('/dashboard/login')
                ->action(DashboardLoginPageAction::class)
                ->name('dashboard.auth.login'),
            Route::post('/dashboard/login')
                ->action(DashboardLoginSubmitAction::class)
                ->name('dashboard.auth.login.submit'),
            Route::post('/dashboard/logout')
                ->action(DashboardLogoutAction::class)
                ->name('dashboard.auth.logout'),
            Route::get('/dashboard')
                ->action(DashboardHomeAction::class)
                ->name('dashboard.home'),
            Route::get('/dashboard/users')
                ->action(DashboardUsersAction::class)
                ->name('dashboard.users.index'),
            Route::get('/dashboard/users/create')
                ->action(DashboardUserCreatePageAction::class)
                ->name('dashboard.users.create'),
            Route::post('/dashboard/users/create')
                ->action(DashboardUserCreateSubmitAction::class)
                ->name('dashboard.users.create.submit'),
            Route::get('/dashboard/users/{id}/edit')
                ->action(DashboardUserEditPageAction::class)
                ->name('dashboard.users.edit'),
            Route::post('/dashboard/users/{id}/edit')
                ->action(DashboardUserEditSubmitAction::class)
                ->name('dashboard.users.edit.submit'),
            Route::get('/dashboard/roles')
                ->action(DashboardRolesAction::class)
                ->name('dashboard.roles.index'),
            Route::get('/dashboard/permissions')
                ->action(DashboardPermissionsAction::class)
                ->name('dashboard.permissions.index'),
            Route::get('/dashboard/activity-log')
                ->action(DashboardActivityLogIndexAction::class)
                ->name('dashboard.activity-log.index'),
            Route::get('/dashboard/activity-log/{id}')
                ->action(DashboardActivityLogViewAction::class)
                ->name('dashboard.activity-log.view'),
        ),
];
