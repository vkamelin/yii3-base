<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120200CreateUserCredentialsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `user_credentials` (
                `user_id` CHAR(36) NOT NULL PRIMARY KEY,
                `password_hash` VARCHAR(255) NOT NULL,
                `password_changed_at` DATETIME(6) NULL,
                `created_at` DATETIME(6) NOT NULL,
                `updated_at` DATETIME(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $b->execute(
            "ALTER TABLE `user_credentials`
                ADD CONSTRAINT `fk_user_credentials_user_id`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE"
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('user_credentials');
    }
}
