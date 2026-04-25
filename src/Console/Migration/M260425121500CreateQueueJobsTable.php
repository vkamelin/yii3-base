<?php

declare(strict_types=1);

namespace App\Console\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M260425121500CreateQueueJobsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->execute(
            <<<'SQL'
            CREATE TABLE `queue_jobs` (
                `id` BINARY(16) NOT NULL,
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
                `last_error` TEXT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_queue_jobs_status_available_at` (`status`, `available_at`),
                KEY `idx_queue_jobs_status_reserved_at` (`status`, `reserved_at`),
                KEY `idx_queue_jobs_type` (`type`),
                KEY `idx_queue_jobs_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('queue_jobs');
    }
}

