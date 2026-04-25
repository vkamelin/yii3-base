<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120100CreateUsersTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `users` (
                `id` CHAR(36) NOT NULL PRIMARY KEY,
                `email` VARCHAR(320) NOT NULL,
                `email_normalized` VARCHAR(320) NOT NULL,
                `name` VARCHAR(160) NOT NULL,
                `status` VARCHAR(32) NOT NULL,
                `created_at` DATETIME(6) NOT NULL,
                `updated_at` DATETIME(6) NOT NULL,
                `deleted_at` DATETIME(6) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $b->execute("CREATE UNIQUE INDEX `uq_users_email_normalized` ON `users` (`email_normalized`)");
        $b->execute("CREATE INDEX `idx_users_status` ON `users` (`status`)");
        $b->execute("CREATE INDEX `idx_users_created_at` ON `users` (`created_at`)");
        $b->execute("CREATE INDEX `idx_users_deleted_at` ON `users` (`deleted_at`)");
        $b->execute("CREATE INDEX `idx_users_status_deleted_created` ON `users` (`status`, `deleted_at`, `created_at`)");
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('users');
    }
}
