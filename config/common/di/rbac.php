<?php

declare(strict_types=1);

use App\Rbac\Domain\Repository\PermissionRepositoryInterface;
use App\Rbac\Domain\Repository\RoleRepositoryInterface;
use App\Rbac\Domain\Service\AccessCheckerInterface;
use App\Rbac\Infrastructure\Persistence\MySqlPermissionRepository;
use App\Rbac\Infrastructure\Persistence\MySqlRbacReadRepository;
use App\Rbac\Infrastructure\Persistence\MySqlRoleRepository;
use App\Rbac\Infrastructure\Persistence\PermissionHydrator;
use App\Rbac\Infrastructure\Persistence\RoleHydrator;
use App\Rbac\Infrastructure\Security\MySqlAccessChecker;

return [
    RoleRepositoryInterface::class => MySqlRoleRepository::class,
    PermissionRepositoryInterface::class => MySqlPermissionRepository::class,
    AccessCheckerInterface::class => MySqlAccessChecker::class,
    RoleHydrator::class => RoleHydrator::class,
    PermissionHydrator::class => PermissionHydrator::class,
    MySqlRbacReadRepository::class => MySqlRbacReadRepository::class,
];
