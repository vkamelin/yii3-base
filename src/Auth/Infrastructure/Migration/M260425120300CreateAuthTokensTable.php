<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120300CreateAuthTokensTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `auth_tokens` (
                `id` CHAR(36) NOT NULL PRIMARY KEY,
                `user_id` CHAR(36) NOT NULL,
                `token_hash` CHAR(32) NOT NULL,
                `type` VARCHAR(32) NOT NULL,
                `name` VARCHAR(120) NULL,
                `abilities` JSON NULL,
                `last_used_at` DATETIME(6) NULL,
                `expires_at` DATETIME(6) NULL,
                `revoked_at` DATETIME(6) NULL,
                `created_at` DATETIME(6) NOT NULL,
                `updated_at` DATETIME(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $b->execute("CREATE UNIQUE INDEX `uq_auth_tokens_token_hash` ON `auth_tokens` (`token_hash`)");
        $b->execute("CREATE INDEX `idx_auth_tokens_user_id` ON `auth_tokens` (`user_id`)");
        $b->execute("CREATE INDEX `idx_auth_tokens_user_type` ON `auth_tokens` (`user_id`, `type`)");
        $b->execute("CREATE INDEX `idx_auth_tokens_expires_at` ON `auth_tokens` (`expires_at`)");
        $b->execute("CREATE INDEX `idx_auth_tokens_revoked_at` ON `auth_tokens` (`revoked_at`)");
        $b->execute(
            "CREATE INDEX `idx_auth_tokens_active_lookup` ON `auth_tokens` (`user_id`, `type`, `revoked_at`, `expires_at`)"
        );

        $b->execute(
            "ALTER TABLE `auth_tokens`
                ADD CONSTRAINT `fk_auth_tokens_user_id`
                FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE"
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('auth_tokens');
    }
}
