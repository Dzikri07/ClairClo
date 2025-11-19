<?php
/**
 * Migration: Update files table
 * Description: Adds user_id, category_id, and other fields to files table
 */

require_once __DIR__ . '/../../app/src/Migration.php';

class UpdateFilesTable extends Migration
{
    public function up()
    {
        $this->log("Updating files table...");

        // Create files table if it doesn't exist
        if (!$this->tableExists('files')) {
            $this->log("Creating files table first...");
            $this->execute("
                CREATE TABLE IF NOT EXISTS `files` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `filename` VARCHAR(255) NOT NULL,
                    `original_name` VARCHAR(255) NOT NULL,
                    `mime` VARCHAR(100) DEFAULT NULL,
                    `size` BIGINT UNSIGNED DEFAULT 0,
                    `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `is_favorite` TINYINT(1) NOT NULL DEFAULT 0,
                    `is_trashed` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    INDEX (`filename`),
                    INDEX (`is_favorite`),
                    INDEX (`is_trashed`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Add user_id column
        $this->addColumn('files', 'user_id', 'INT UNSIGNED DEFAULT NULL AFTER id');
        
        // Add category_id column
        $this->addColumn('files', 'category_id', 'INT UNSIGNED DEFAULT NULL AFTER user_id');
        
        // Add file_path column (relative path from uploads directory)
        $this->addColumn('files', 'file_path', 'VARCHAR(500) DEFAULT NULL AFTER filename');
        
        // Add thumbnail_path for images and videos
        $this->addColumn('files', 'thumbnail_path', 'VARCHAR(500) DEFAULT NULL AFTER file_path');
        
        // Add file extension column
        $this->addColumn('files', 'extension', 'VARCHAR(20) DEFAULT NULL AFTER mime');
        
        // Add description column
        $this->addColumn('files', 'description', 'TEXT DEFAULT NULL AFTER original_name');
        
        // Add download_count column
        $this->addColumn('files', 'download_count', 'INT UNSIGNED DEFAULT 0 AFTER size');
        
        // Add last_accessed column
        $this->addColumn('files', 'last_accessed', 'TIMESTAMP NULL DEFAULT NULL AFTER uploaded_at');
        
        // Add trashed_at column
        $this->addColumn('files', 'trashed_at', 'TIMESTAMP NULL DEFAULT NULL AFTER is_trashed');
        
        // Add foreign key constraints
        $this->log("Adding foreign key constraints...");
        
        // Check if foreign keys already exist before adding
        $fkUserExists = $this->foreignKeyExists('files', 'fk_files_user');
        $fkCategoryExists = $this->foreignKeyExists('files', 'fk_files_category');
        
        if (!$fkUserExists) {
            $this->execute("
                ALTER TABLE `files` 
                ADD CONSTRAINT `fk_files_user` 
                FOREIGN KEY (`user_id`) 
                REFERENCES `users`(`id`) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE
            ");
        }
        
        if (!$fkCategoryExists) {
            $this->execute("
                ALTER TABLE `files` 
                ADD CONSTRAINT `fk_files_category` 
                FOREIGN KEY (`category_id`) 
                REFERENCES `file_categories`(`id`) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE
            ");
        }
        
        // Add indexes for better performance
        $this->log("Adding indexes...");
        
        if (!$this->indexExists('files', 'idx_user_id')) {
            $this->execute("CREATE INDEX `idx_user_id` ON `files` (`user_id`)");
        }
        
        if (!$this->indexExists('files', 'idx_category_id')) {
            $this->execute("CREATE INDEX `idx_category_id` ON `files` (`category_id`)");
        }
        
        if (!$this->indexExists('files', 'idx_extension')) {
            $this->execute("CREATE INDEX `idx_extension` ON `files` (`extension`)");
        }
        
        if (!$this->indexExists('files', 'idx_uploaded_at')) {
            $this->execute("CREATE INDEX `idx_uploaded_at` ON `files` (`uploaded_at`)");
        }

        $this->log("Files table updated successfully");
        return true;
    }

    public function down()
    {
        $this->log("Reverting files table changes...");

        // Drop foreign keys first
        $this->execute("ALTER TABLE `files` DROP FOREIGN KEY IF EXISTS `fk_files_user`");
        $this->execute("ALTER TABLE `files` DROP FOREIGN KEY IF EXISTS `fk_files_category`");
        
        // Drop indexes
        $this->execute("DROP INDEX IF EXISTS `idx_user_id` ON `files`");
        $this->execute("DROP INDEX IF EXISTS `idx_category_id` ON `files`");
        $this->execute("DROP INDEX IF EXISTS `idx_extension` ON `files`");
        $this->execute("DROP INDEX IF EXISTS `idx_uploaded_at` ON `files`");
        
        // Drop columns
        $this->dropColumn('files', 'user_id');
        $this->dropColumn('files', 'category_id');
        $this->dropColumn('files', 'file_path');
        $this->dropColumn('files', 'thumbnail_path');
        $this->dropColumn('files', 'extension');
        $this->dropColumn('files', 'description');
        $this->dropColumn('files', 'download_count');
        $this->dropColumn('files', 'last_accessed');
        $this->dropColumn('files', 'trashed_at');

        $this->log("Files table reverted successfully");
        return true;
    }

    /**
     * Check if foreign key exists
     */
    private function foreignKeyExists($tableName, $fkName)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ? 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $stmt->execute([$tableName, $fkName]);
        return $stmt->fetchColumn() > 0;
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
