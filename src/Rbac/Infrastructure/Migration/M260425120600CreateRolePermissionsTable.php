<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120600CreateRolePermissionsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `role_permissions` (
                `role_id` CHAR(36) NOT NULL,
                `permission_id` CHAR(36) NOT NULL,
                `created_at` DATETIME(6) NOT NULL,
                PRIMARY KEY (`role_id`, `permission_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $b->execute("CREATE INDEX `idx_role_permissions_permission_id` ON `role_permissions` (`permission_id`)");

        $b->execute(
            "ALTER TABLE `role_permissions`
                ADD CONSTRAINT `fk_role_permissions_role_id`
                FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE"
        );
        $b->execute(
            "ALTER TABLE `role_permissions`
                ADD CONSTRAINT `fk_role_permissions_permission_id`
                FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE"
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('role_permissions');
    }
}
