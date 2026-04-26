<?php

declare(strict_types=1);

namespace App\Shared\Application\Audit\Action;

final class SystemAuditAction
{
    public const SEED_EXECUTED = 'system.seed.executed';
    public const MIGRATION_EXECUTED = 'system.migration.executed';
    public const CONFIG_CHANGED = 'system.config.changed';

    private function __construct()
    {
    }
}
