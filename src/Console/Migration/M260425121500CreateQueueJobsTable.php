<?php

declare(strict_types=1);

namespace App\Console\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425121500CreateQueueJobsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute("CREATE TABLE `queue_jobs` (
            `id` BINARY(16) NOT NULL PRIMARY KEY,
            `type` VARCHAR(120) NOT NULL,
            `payload` JSON NOT NULL,
            `status` VARCHAR(20) NOT NULL,
            `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
            `max_attempts` INT UNSIGNED NOT NULL DEFAULT 3,
            `available_at` DATETIME(6) NOT NULL,
            `reserved_at` DATETIME(6) NULL,
            `failed_at` DATETIME(6) NULL,
            `created_at` DATETIME(6) NOT NULL,
            `updated_at` DATETIME(6) NOT NULL,
            `last_error` TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $b->execute("CREATE INDEX `idx_queue_jobs_status_available_at` ON `queue_jobs` (`status`, `available_at`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_status_reserved_at` ON `queue_jobs` (`status`, `reserved_at`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_type` ON `queue_jobs` (`type`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_created_at` ON `queue_jobs` (`created_at`)");

    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('queue_jobs');
    }
}

