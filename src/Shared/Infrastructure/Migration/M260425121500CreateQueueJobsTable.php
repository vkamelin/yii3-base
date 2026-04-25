<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425121500CreateQueueJobsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            "CREATE TABLE `queue_jobs` (
                `id` CHAR(36) NOT NULL PRIMARY KEY,
                `queue` VARCHAR(80) NOT NULL,
                `job_type` VARCHAR(190) NOT NULL,
                `payload` JSON NOT NULL,
                `status` VARCHAR(32) NOT NULL,
                `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                `max_attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 3,
                `available_at` DATETIME(6) NOT NULL,
                `reserved_at` DATETIME(6) NULL,
                `reserved_by` VARCHAR(120) NULL,
                `failed_at` DATETIME(6) NULL,
                `last_error` TEXT NULL,
                `created_at` DATETIME(6) NOT NULL,
                `updated_at` DATETIME(6) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );

        $b->execute("CREATE INDEX `idx_queue_jobs_ready` ON `queue_jobs` (`queue`, `status`, `available_at`, `id`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_status_available` ON `queue_jobs` (`status`, `available_at`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_reserved` ON `queue_jobs` (`reserved_at`, `reserved_by`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_failed_at` ON `queue_jobs` (`failed_at`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_created_at` ON `queue_jobs` (`created_at`)");
        $b->execute("CREATE INDEX `idx_queue_jobs_job_type` ON `queue_jobs` (`job_type`)");
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('queue_jobs');
    }
}
