<?php
/**
 * Migration: Create file storage paths table
 * Description: Creates a table to track file storage paths and metadata for enhanced file management
 */

require_once __DIR__ . '/../../app/src/Migration.php';

class CreateFileStoragePathsTable extends Migration
{
    public function up()
    {
        $this->log("Creating file_storage_paths table...");

        $sql = "CREATE TABLE IF NOT EXISTS `file_storage_paths` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `file_id` INT UNSIGNED NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `storage_path` VARCHAR(500) NOT NULL COMMENT 'Path: /uploads/user_<user_id>/',
            `file_path` VARCHAR(500) NOT NULL COMMENT 'Full relative path from uploads directory',
            `thumbnail_path` VARCHAR(500) DEFAULT NULL COMMENT 'Thumbnail path if applicable',
            `original_filename` VARCHAR(255) NOT NULL,
            `stored_filename` VARCHAR(255) NOT NULL,
            `file_size` BIGINT UNSIGNED DEFAULT 0 COMMENT 'File size in bytes',
            `mime_type` VARCHAR(100) DEFAULT NULL,
            `file_extension` VARCHAR(20) DEFAULT NULL,
            `download_count` INT UNSIGNED DEFAULT 0 COMMENT 'Number of times downloaded',
            `last_downloaded` TIMESTAMP NULL DEFAULT NULL,
            `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Soft delete flag',
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_file_id` (`file_id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_file_extension` (`file_extension`),
            INDEX `idx_download_count` (`download_count`),
            INDEX `idx_is_deleted` (`is_deleted`),
            CONSTRAINT `fk_file_storage_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_file_storage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='File storage paths and metadata tracking'";

        if ($this->execute($sql)) {
            $this->log("File storage paths table created successfully");
            
            // Create indexes for performance
            $this->createIndexes();
            
            return true;
        }

        return false;
    }

    public function down()
    {
        $this->log("Dropping file_storage_paths table...");
        
        if ($this->dropTable('file_storage_paths')) {
            $this->log("File storage paths table dropped successfully");
            return true;
        }

        return false;
    }

    /**
     * Create additional indexes for better query performance
     */
    private function createIndexes()
    {
        $this->log("Creating additional indexes...");
        
        $indexes = [
            'idx_user_created' => 'CREATE INDEX `idx_user_created` ON `file_storage_paths` (`user_id`, `created_at` DESC)',
            'idx_storage_path' => 'CREATE INDEX `idx_storage_path` ON `file_storage_paths` (`storage_path`)',
            'idx_deleted_timestamp' => 'CREATE INDEX `idx_deleted_timestamp` ON `file_storage_paths` (`is_deleted`, `deleted_at`)',
        ];

        foreach ($indexes as $name => $sql) {
            try {
                if (!$this->indexExists('file_storage_paths', $name)) {
                    $this->execute($sql);
                    $this->log("  - Index {$name} created");
                }
            } catch (Exception $e) {
                $this->log("  - Warning: Failed to create index {$name}: " . $e->getMessage());
            }
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($tableName, $indexName)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ");
        $stmt->execute([$tableName, $indexName]);
        return $stmt->fetchColumn() > 0;
    }
}
?>
