<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425120800CreateActivityLogsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `activity_logs` (
                `id` BINARY(16) NOT NULL PRIMARY KEY,
                `actor_user_id` BINARY(16) NULL,
                `actor_type` VARCHAR(32) NOT NULL DEFAULT 'user',
                `action` VARCHAR(120) NOT NULL,
                `entity_type` VARCHAR(120) NULL,
                `entity_id` BINARY(16) NULL,
                `ip` VARCHAR(45) NULL,
                `user_agent` VARCHAR(512) NULL,
                `request_id` VARCHAR(64) NULL,
                `source` VARCHAR(32) NOT NULL,
                `payload` JSON NULL,
                `created_at` DATETIME(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        );

        $b->execute("CREATE INDEX `idx_activity_logs_actor_user_id` ON `activity_logs` (`actor_user_id`)");
        $b->execute("CREATE INDEX `idx_activity_logs_action` ON `activity_logs` (`action`)");
        $b->execute("CREATE INDEX `idx_activity_logs_entity` ON `activity_logs` (`entity_type`, `entity_id`)");
        $b->execute("CREATE INDEX `idx_activity_logs_request_id` ON `activity_logs` (`request_id`)");
        $b->execute("CREATE INDEX `idx_activity_logs_created_at` ON `activity_logs` (`created_at`)");
        $b->execute("CREATE INDEX `idx_activity_logs_source_created_at` ON `activity_logs` (`source`, `created_at`)");
        $b->execute("CREATE INDEX `idx_activity_logs_actor_created_at` ON `activity_logs` (`actor_user_id`, `created_at`)");
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('activity_logs');
    }
}
