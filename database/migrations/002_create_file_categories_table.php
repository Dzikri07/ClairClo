<?php
/**
 * Migration: Create file_categories table
 * Description: Creates table for organizing different file types
 */

require_once __DIR__ . '/../../app/src/Migration.php';

class CreateFileCategoriesTable extends Migration
{
    public function up()
    {
        $this->log("Creating file_categories table...");

        $sql = "CREATE TABLE IF NOT EXISTS `file_categories` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) NOT NULL,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `description` TEXT DEFAULT NULL,
            `allowed_extensions` TEXT NOT NULL COMMENT 'Comma-separated list of allowed extensions',
            `max_file_size` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Max file size in bytes (NULL = no limit)',
            `icon` VARCHAR(50) DEFAULT NULL COMMENT 'Icon class or name',
            `color` VARCHAR(20) DEFAULT NULL COMMENT 'Category color code',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_slug` (`slug`),
            INDEX `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='File categories for organizing uploads'";

        if ($this->execute($sql)) {
            $this->log("File_categories table created successfully");
            return true;
        }

        return false;
    }

    public function down()
    {
        $this->log("Dropping file_categories table...");
        
        if ($this->dropTable('file_categories')) {
            $this->log("File_categories table dropped successfully");
            return true;
        }

        return false;
    }
}
