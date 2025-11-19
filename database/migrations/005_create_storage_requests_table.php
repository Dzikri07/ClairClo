<?php
/**
 * Migration: Create storage_requests table
 * Description: Creates the storage_requests table for user storage increase requests
 */

require_once __DIR__ . '/../../app/src/Migration.php';

class CreateStorageRequestsTable extends Migration
{
    public function up()
    {
        $this->log("Creating storage_requests table...");

        $sql = "CREATE TABLE IF NOT EXISTS `storage_requests` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `requested_quota` BIGINT UNSIGNED NOT NULL COMMENT 'Requested storage quota in bytes',
            `current_quota` BIGINT UNSIGNED NOT NULL COMMENT 'Current storage quota in bytes',
            `reason` TEXT DEFAULT NULL,
            `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            `admin_id` INT UNSIGNED DEFAULT NULL COMMENT 'Admin who processed the request',
            `admin_notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `processed_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_created_at` (`created_at`),
            CONSTRAINT `fk_storage_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_storage_requests_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Storage increase requests table'";

        if ($this->execute($sql)) {
            $this->log("Storage_requests table created successfully");
            return true;
        }

        return false;
    }

    public function down()
    {
        $this->log("Dropping storage_requests table...");

        if ($this->dropTable('storage_requests')) {
            $this->log("Storage_requests table dropped successfully");
            return true;
        }

        return false;
    }
}
