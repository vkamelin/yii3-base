<?php

declare(strict_types=1);

namespace App\Rbac\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120700CreateUserRolesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `user_roles` (
                `user_id` CHAR(36) NOT NULL,
                `role_id` CHAR(36) NOT NULL,
                `created_at` DATETIME(6) NOT NULL,
                PRIMARY KEY (`user_id`, `role_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        );

        $b->execute("CREATE INDEX `idx_user_roles_role_id` ON `user_roles` (`role_id`)");

        $b->execute(
            "ALTER TABLE `user_roles`
                ADD CONSTRAINT `fk_user_roles_user_id`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE",
        );
        $b->execute(
            "ALTER TABLE `user_roles`
                ADD CONSTRAINT `fk_user_roles_role_id`
                FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE",
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('user_roles');
    }
}
