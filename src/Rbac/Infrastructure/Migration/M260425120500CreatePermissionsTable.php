<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120500CreatePermissionsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `permissions` (
                `id` CHAR(36) NOT NULL PRIMARY KEY,
                `code` VARCHAR(120) NOT NULL,
                `name` VARCHAR(160) NOT NULL,
                `description` VARCHAR(500) NULL,
                `group_code` VARCHAR(80) NOT NULL,
                `is_system` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME(6) NOT NULL,
                `updated_at` DATETIME(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $b->execute("CREATE UNIQUE INDEX `uq_permissions_code` ON `permissions` (`code`)");
        $b->execute("CREATE INDEX `idx_permissions_group_code` ON `permissions` (`group_code`)");
        $b->execute("CREATE INDEX `idx_permissions_is_system` ON `permissions` (`is_system`)");
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('permissions');
    }
}
