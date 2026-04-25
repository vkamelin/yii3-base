<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120400CreateRolesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `roles` (
                `id` CHAR(36) NOT NULL PRIMARY KEY,
                `code` VARCHAR(80) NOT NULL,
                `name` VARCHAR(120) NOT NULL,
                `description` VARCHAR(500) NULL,
                `is_system` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME(6) NOT NULL,
                `updated_at` DATETIME(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $b->execute("CREATE UNIQUE INDEX `uq_roles_code` ON `roles` (`code`)");
        $b->execute("CREATE INDEX `idx_roles_is_system` ON `roles` (`is_system`)");
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('roles');
    }
}
